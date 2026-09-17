<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSanctumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.guards.api.driver' => 'sanctum']);
    }


    public function test_that_user_can_register_successfully()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token_type',
                'access_token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified',
                    'role',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'message' => 'User successfully created. Please check your email to verify your account.',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'email_verified' => false,
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_that_register_validates_required_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_that_register_validates_email_format()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_that_register_validates_password_confirmation()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_that_register_validates_unique_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_that_user_can_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $credentials);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified',
                    'role',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'user' => [
                    'email' => 'john@example.com',
                    'email_verified' => true,
                ]
            ]);
    }

    public function test_that_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $credentials);

        $response->assertStatus(401);
    }

    public function test_that_login_validates_required_fields()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_that_authenticated_user_can_get_their_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->hasVerifiedEmail(),
                ]
            ]);
    }

    public function test_that_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout successful']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_that_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_that_validate_token_returns_true_with_valid_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/auth/validate-token', [
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->hasVerifiedEmail(),
                ]
            ]);
    }

    public function test_that_validate_token_returns_false_with_invalid_token()
    {
        $response = $this->postJson('/api/auth/validate-token', [
            'token' => 'invalid-token-123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['valid' => false]);
    }

    public function test_that_validate_token_validates_token_required()
    {
        $response = $this->postJson('/api/auth/validate-token', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_that_password_is_hashed_during_registration()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'plain-password',
            'password_confirmation' => 'plain-password',
        ];

        $this->postJson('/api/auth/register', $userData);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(Hash::check('plain-password', $user->password));
    }

    public function test_that_user_role_is_set_correctly_during_registration()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals('user', $user->role->value);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isUser());
    }

    public function test_that_user_cannot_register_with_admin_role()
    {
        $userData = [
            'name' => 'Admin Wannabe',
            'email' => 'adminwannabe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'adminwannabe@example.com',
            'role' => 'user'
        ]);

        $response->assertJson([
            'user' => [
                'role' => 'user'
            ]
        ]);
    }

    public function test_that_user_cannot_register_with_any_custom_role()
    {
        $userData = [
            'name' => 'Custom Role User',
            'email' => 'customrole@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'moderator'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'customrole@example.com',
            'role' => 'user'
        ]);
    }

    public function test_that_registered_user_has_default_user_role()
    {
        $userData = [
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'default@example.com',
            'role' => 'user'
        ]);

        $response->assertJson([
            'user' => [
                'role' => 'user'
            ]
        ]);
    }
}
