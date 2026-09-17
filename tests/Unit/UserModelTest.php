<?php

namespace Tests\Unit;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_that_it_has_correct_fillable_attributes()
    {
        $user = new User();

        $this->assertEquals([
            'name',
            'email',
            'password',
            'role',
        ], $user->getFillable());
    }

    public function test_that_it_has_correct_hidden_attributes()
    {
        $user = new User();
        
        $this->assertEquals([
            'password',
            'remember_token',
        ], $user->getHidden());
    }

    public function test_that_it_has_correct_casts()
    {
        $user = new User();
        
        $casts = $user->getCasts();

        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertEquals('hashed', $casts['password']);
        $this->assertEquals(UserRole::class, $casts['role']);
    }

    public function test_that_it_can_create_a_regular_user()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(UserRole::USER, $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isUser());
    }

    public function test_that_it_creates_user_with_default_role_as_user()
    {
        $user = User::factory()->create([
            'name' => 'Default User',
            'email' => 'default@example.com',
        ]);

        $this->assertEquals(UserRole::USER, $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isUser());
    }

    public function test_that_it_can_create_admin_user()
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isUser());
    }

    public function test_that_it_rejects_invalid_roles()
    {
        $this->expectException(\ValueError::class);
        
        User::factory()->create([
            'name' => 'Invalid User',
            'email' => 'invalid@example.com',
            'role' => 'invalid-role',
        ]);
    }

    public function test_that_it_can_check_user_roles_correctly()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($admin->isUser());
        $this->assertTrue($user->isUser());
    }

    public function test_that_it_only_accepts_valid_roles()
    {
        $user = User::factory()->user()->create();
        $admin = User::factory()->admin()->create();

        $this->assertEquals(UserRole::USER, $user->role);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
    }

    public function test_that_it_can_scope_users_by_role()
    {
        User::factory()->admin()->create();
        User::factory()->user()->create();
        User::factory()->user()->create();
        User::factory()->user()->create();

        $admins = User::admins()->get();
        $users = User::users()->get();

        $this->assertCount(1, $admins);
        $this->assertEquals(UserRole::ADMIN, $admins->first()->role);
        $this->assertTrue($admins->first()->isAdmin());

        $this->assertCount(3, $users);
        $this->assertTrue($users->every(fn($user) => $user->role === UserRole::USER));
        $this->assertTrue($users->every(fn($user) => !$user->isAdmin()));
    }

    public function test_that_it_can_check_email_verification_status()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->unverified()->create();

        $this->assertTrue($verifiedUser->hasVerifiedEmail());
        $this->assertFalse($unverifiedUser->hasVerifiedEmail());
    }

    public function test_that_it_automatically_hashes_passwords()
    {
        $user = User::factory()->create([
            'password' => 'plain-password',
        ]);

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(password_verify('plain-password', $user->password));
    }

    public function test_that_it_can_generate_api_tokens()
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertNotNull($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }

    public function test_that_it_can_retrieve_user_by_email_and_role()
    {
        $user = User::factory()->create([
            'email' => 'unique@example.com',
        ]);

        $foundUser = User::where('email', 'unique@example.com')->first();

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals(UserRole::USER, $foundUser->role);
        $this->assertFalse($foundUser->isAdmin());
    }

    public function test_that_it_can_filter_users_by_specific_roles()
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->user()->create();
        $user2 = User::factory()->user()->create();

        $regularUsers = User::where('role', UserRole::USER->value)->get();
        $adminUsers = User::where('role', UserRole::ADMIN->value)->get();

        $this->assertCount(2, $regularUsers);
        $this->assertCount(1, $adminUsers);
        $this->assertEquals($admin->id, $adminUsers->first()->id);
        $this->assertTrue($adminUsers->first()->isAdmin());
    }

    public function test_that_admin_users_have_different_permissions()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($admin->canManageUsers());
        $this->assertFalse($user->canManageUsers());
    }

    public function test_that_factory_states_work_correctly()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create();
        $unverifiedUser = User::factory()->unverified()->create();
        $unverifiedAdmin = User::factory()->admin()->unverified()->create();

        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertEquals(UserRole::USER, $user->role);
        $this->assertNull($unverifiedUser->email_verified_at);
        $this->assertNull($unverifiedAdmin->email_verified_at);
        $this->assertEquals(UserRole::ADMIN, $unverifiedAdmin->role);
    }
}