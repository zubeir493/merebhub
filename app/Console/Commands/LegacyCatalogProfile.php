<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('legacy:catalog-profile
    {--connection= : Database connection containing the legacy tables}
    {--json : Render the profile as JSON}')]
#[Description('Profile the legacy catalog tables without importing or mutating data')]
class LegacyCatalogProfile extends Command
{
    public function handle(): int
    {
        $connection = (string) ($this->option('connection') ?: config('database.default'));
        $schema = Schema::connection($connection);
        $database = DB::connection($connection);
        $requiredTables = ['authors', 'products', 'platforms', 'platform_product'];
        $tables = [];

        foreach ($requiredTables as $table) {
            $tables[$table] = $schema->hasTable($table);
        }

        $profile = [
            'connection' => $connection,
            'source_available' => count(array_filter($tables)) === count($requiredTables),
            'tables' => $tables,
            'counts' => [],
        ];
        $sourceTableCount = count(array_filter($tables));

        if ($profile['source_available']) {
            foreach ($requiredTables as $table) {
                $profile['counts'][$table] = $database->table($table)->count();
            }
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif (! $profile['source_available']) {
            if ($sourceTableCount > 0) {
                $this->error('The legacy catalog source is incomplete; no import was attempted.');
                $this->line('Expected tables: '.implode(', ', $requiredTables).'.');

                return self::FAILURE;
            }

            $this->info('No complete legacy catalog source was found; nothing was imported.');
            $this->line('Expected tables: '.implode(', ', $requiredTables).'.');
        } else {
            $this->info('Legacy catalog source is available.');

            foreach ($profile['counts'] as $table => $count) {
                $this->line(sprintf('%-16s %d rows', $table, $count));
            }
        }

        return $sourceTableCount > 0 && ! $profile['source_available']
            ? self::FAILURE
            : self::SUCCESS;
    }
}
