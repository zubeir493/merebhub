<?php

namespace App\Support;

use App\Models\IntegrationSetting;

class IntegrationSettingsStore
{
    public function get(string $provider, string $key, mixed $default = null): mixed
    {
        $setting = IntegrationSetting::query()
            ->where('provider', $provider)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    public function put(string $provider, string $key, mixed $value): IntegrationSetting
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return IntegrationSetting::query()->updateOrCreate(
            ['provider' => $provider, 'key' => $key],
            ['value' => $value],
        );
    }
}
