<?php

namespace App\Policies;

use App\Models\DownloadableAsset;
use App\Models\Entitlement;
use App\Models\User;

class DownloadableAssetPolicy
{
    public function download(User $user, DownloadableAsset $asset): bool
    {
        return $asset->hasCleanScan()
            && Entitlement::query()
                ->whereBelongsTo($user)
                ->where('product_id', $asset->product_id)
                ->where('status', 'active')
                ->exists();
    }
}
