<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function shallow(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
        ]);
    }

    public function deep(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'private_filesystem' => [
                'status' => 'ok',
                'disk' => config('marketplace.private_files_disk'),
            ],
        ];
        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array{status: string, detail?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::select('select 1');

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'failed', 'detail' => 'database unavailable'];
        }
    }

    /**
     * @return array{status: string, detail?: string}
     */
    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'failed', 'detail' => 'redis unavailable'];
        }
    }
}
