<?php

namespace App\Domain\Fulfillment\Actions;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Models\Credential;
use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RevealCredentialAction
{
    public function __construct(private readonly RecordAuditEventAction $audit) {}

    public function handle(Credential $credential, User $user): string
    {
        return DB::transaction(function () use ($credential, $user): string {
            $credential = Credential::query()->lockForUpdate()->findOrFail($credential->getKey());

            $entitlement = Entitlement::query()
                ->whereKey($credential->entitlement_id)
                ->whereBelongsTo($user)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($entitlement === null) {
                throw (new ModelNotFoundException)->setModel(Entitlement::class, $credential->entitlement_id);
            }

            $credential->forceFill(['revealed_at' => now()])->save();

            $this->audit->handle(
                'credential.revealed',
                actor: $user,
                subject: $credential,
                metadata: [
                    'credential_id' => $credential->public_id,
                    'credential_type' => $credential->type,
                    'entitlement_id' => $credential->entitlement_id,
                ],
            );

            return (string) $credential->secret;
        });
    }
}
