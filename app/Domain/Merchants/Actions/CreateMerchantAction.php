<?php

namespace App\Domain\Merchants\Actions;

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CreateMerchantAction
{
    public function __construct(private DatabaseManager $database) {}

    /**
     * @param  array{type: MerchantType|string, display_name: string, legal_name?: string|null, slug?: string|null, profile?: string|null, website_url?: string|null, status?: MerchantStatus|string, approved_at?: Carbon|null}  $data
     */
    public function handle(User $owner, array $data): Merchant
    {
        return $this->database->transaction(
            fn (): Merchant => $this->createMerchant($owner, $data),
        );
    }

    /**
     * @param  array{name: string, email: string, password: string}  $accountData
     * @param  array{type: MerchantType|string, display_name: string, legal_name?: string|null, slug?: string|null, profile?: string|null, website_url?: string|null, status?: MerchantStatus|string, approved_at?: Carbon|null}  $data
     */
    public function handleWithNewOwner(array $accountData, array $data): Merchant
    {
        return $this->database->transaction(function () use ($accountData, $data): Merchant {
            $owner = User::query()->create($accountData);
            $owner->forceFill(['email_verified_at' => now()])->save();

            return $this->createMerchant($owner, $data);
        });
    }

    /**
     * @param  array{type: MerchantType|string, display_name: string, legal_name?: string|null, slug?: string|null, profile?: string|null, website_url?: string|null, status?: MerchantStatus|string, approved_at?: Carbon|null}  $data
     */
    private function createMerchant(User $owner, array $data): Merchant
    {
        $displayName = $data['display_name'];
        $status = ($data['status'] ?? null) instanceof MerchantStatus
            ? $data['status']
            : MerchantStatus::from($data['status'] ?? MerchantStatus::Pending->value);

        $merchant = Merchant::query()->create([
            'public_id' => (string) Str::ulid(),
            'type' => $data['type'] instanceof MerchantType ? $data['type'] : MerchantType::from($data['type']),
            'display_name' => $displayName,
            'legal_name' => $data['legal_name'] ?? null,
            'slug' => $data['slug'] ?? Str::slug($displayName),
            'profile' => $data['profile'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'status' => $status,
            'approved_at' => $status === MerchantStatus::Approved ? ($data['approved_at'] ?? now()) : null,
        ]);

        $merchant->memberships()->create([
            'user_id' => $owner->getKey(),
            'merchant_role' => MerchantMembershipRole::Owner,
            'status' => MerchantMembershipStatus::Active,
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $owner->forceFill(['merchant_access' => true])->save();

        return $merchant;
    }
}
