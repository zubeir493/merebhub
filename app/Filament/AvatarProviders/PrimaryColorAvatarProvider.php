<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class PrimaryColorAvatarProvider implements AvatarProvider
{
    public static function generateGravatarUrl(string $email, ?int $size = null): string
    {
        $hash = md5(strtolower(trim($email)));

        return 'https://www.gravatar.com/avatar/'.$hash.'?'.http_build_query(array_filter([
            'd' => 'mp',
            's' => $size,
        ]));
    }

    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->filter()
            ->map(fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1)))
            ->take(2)
            ->join('');

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="10" fill="#4f46e5"/><text x="32" y="34" dominant-baseline="middle" text-anchor="middle" fill="#fff" font-family="Arial,sans-serif" font-size="24" font-weight="700">%s</text></svg>',
            e($initials),
        );

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
}
