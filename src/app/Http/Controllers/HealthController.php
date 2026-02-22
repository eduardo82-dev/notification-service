<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

final class HealthController extends Controller
{
    #[OA\Get(
        path: '/api/v1/health',
        operationId: 'healthCheck',
        summary: 'System health check',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All systems healthy',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',  type: 'string', example: 'ok'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(
                            property: 'checks',
                            properties: [
                                new OA\Property(property: 'database', type: 'string', example: 'ok'),
                                new OA\Property(property: 'redis',    type: 'string', example: 'ok'),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 503,
                description: 'One or more dependencies unhealthy',
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $allOk  = true;

        // MySQL check
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
            $allOk = false;
        }

        // Redis check
        try {
            Cache::store('redis')->put('_health_check', '1', 5);
            $checks['redis'] = 'ok';
        } catch (\Throwable $e) {
            $checks['redis'] = 'error: ' . $e->getMessage();
            $allOk = false;
        }

        return response()->json([
            'status'  => $allOk ? 'ok' : 'degraded',
            'version' => config('app.version', '1.0.0'),
            'checks'  => $checks,
        ], $allOk ? 200 : 503);
    }
}
