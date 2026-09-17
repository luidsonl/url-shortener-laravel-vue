<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class ProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.guards.api.driver' => 'sanctum']);
        $this->artisan('migrate:fresh');
    }

    protected function loginAndGetToken($email, $password = 'password')
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        return $response->json('access_token');
    }

    public function test_regular_user_can_view_their_own_profile()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/profile/");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_regular_user_cannot_change_their_own_role()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'user@example.com',
            'role' => 'admin' 
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/profile/", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => 'user'
        ]);
    }

    public function test_regular_user_can_update_their_profile_name()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'Original Name'
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/profile/", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'Updated Name',
                'email' => 'user@example.com'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_unverified_regular_user_can_access_their_own_profile()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => null,
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');


        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/profile/");


        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);

    }
}
