<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckTest extends TestCase
{
    private const HEALTHCHECK_ENDPOINT = '/api/health';

    public function test_that_healthcheck_returns_200_and_healthy_status()
    {
        $response = $this->get(self::HEALTHCHECK_ENDPOINT);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'healthy'
                ]);
    }

    public function test_that_healthcheck_returns_correct_json_structure()
    {
        $response = $this->get(self::HEALTHCHECK_ENDPOINT);

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'services' => [
                'database'
            ]
        ]);
    }

    public function test_that_healthcheck_includes_environment_info()
    {
        $response = $this->get(self::HEALTHCHECK_ENDPOINT);

        $response->assertJsonFragment([
            'environment' => app()->environment()
        ]);
    }

    public function test_that_healthcheck_returns_503_when_database_is_down()
    {
        DB::shouldReceive('connection->getPdo')
          ->andThrow(new \Exception('Database connection failed'));

        $response = $this->get(self::HEALTHCHECK_ENDPOINT);

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'services' => [
                    'database' => 'disconnected'
                ]
            ]);
    }

}