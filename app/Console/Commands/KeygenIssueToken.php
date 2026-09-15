<?php

namespace App\Console\Commands;

use App\Integrations\Keygen\KeygenClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('keygen:issue-token {--email=}')]
#[Description('Issue a server-side Keygen API token from administrator credentials')]
class KeygenIssueToken extends Command
{
    public function handle(KeygenClient $client): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('Keygen administrator email'));
        $password = (string) $this->secret('Keygen administrator password');

        if (blank($email) || blank($password)) {
            $this->error('Both Keygen credentials are required.');

            return self::FAILURE;
        }

        try {
            $token = $client->issueToken($email, $password);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Keygen did not issue a token. Check the endpoint and credentials.');

            return self::FAILURE;
        }

        $this->line($token);

        return self::SUCCESS;
    }
}
