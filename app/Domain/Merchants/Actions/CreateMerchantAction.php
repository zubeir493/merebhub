<?php

namespace App\Domain\Merchants\Actions;

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

class CreateMerchantAction
{
    public function __construct(private DatabaseManager $database) {}

    /**
     * @param  array{type: MerchantType|string, display_name: string, legal_name?: string|null, slug?: string|null, profile?: string|null, website_url?: string|null}  $data
     */
    public function handle(User $owner, array $data): Merchant
    {
        return $this->database->transaction(function () use ($owner, $data): Merchant {
            $displayName = $data['display_name'];
            $merchant = Merchant::query()->create([
                'public_id' => (string) Str::ulid(),
                'type' => $data['type'] instanceof MerchantType ? $data['type'] : MerchantType::from($data['type']),
                'display_name' => $displayName,
                'legal_name' => $data['legal_name'] ?? null,
                'slug' => $data['slug'] ?? Str::slug($displayName),
                'profile' => $data['profile'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'status' => MerchantStatus::Pending,
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
        });
    }
}
