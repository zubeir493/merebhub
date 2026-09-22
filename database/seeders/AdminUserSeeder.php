<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('MEREBHUB_ADMIN_EMAIL', ''));
        $password = (string) env('MEREBHUB_ADMIN_PASSWORD', '');

        if ($email === '' && $password === '') {
            return;
        }

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('Set both MEREBHUB_ADMIN_EMAIL and MEREBHUB_ADMIN_PASSWORD to seed an admin user.');
        }

        $this->upsert(
            email: $email,
            password: $password,
            firstName: (string) env('MEREBHUB_ADMIN_FIRST_NAME', 'Admin'),
            lastName: (string) env('MEREBHUB_ADMIN_LAST_NAME', 'User'),
        );
    }

    public function upsert(string $email, string $password, string $firstName = 'Admin', string $lastName = 'User'): Staff
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('The admin email address is invalid.');
        }

        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('The admin password must be at least 8 characters long.');
        }

        return Staff::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => trim($firstName) ?: 'Admin',
                'last_name' => trim($lastName) ?: 'User',
                'admin' => true,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
