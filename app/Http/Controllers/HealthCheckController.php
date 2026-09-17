<?php
// app/Http/Controllers/HealthCheckController.php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function check(): JsonResponse
    {
        $status = 'healthy';
        
        try {
            DB::connection()->getPdo();
            $database = 'connected';
        } catch (\Exception $e) {
            $database = 'disconnected';
            $status = 'unhealthy';
        }
        
        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'services' => [
                'database' => $database
            ]
        ], $status === 'healthy' ? 200 : 503);
    }
}