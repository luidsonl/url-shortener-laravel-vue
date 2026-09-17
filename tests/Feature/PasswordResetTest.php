<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Mail\ResetPasswordLink;
use Illuminate\Support\Str;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_email_with_link()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/forgot-password', ['email' => 'test@example.com']);

        $response->assertStatus(200)
            ->assertJson(['message' => 'If this email exists, a reset link has been sent.']);

        Mail::assertSent(ResetPasswordLink::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'test@example.com']);
    }

    public function test_reset_password_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('old-password'),
        ]);

        $token = Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => 'test@example.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password reset successfully']);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'test@example.com']);
    }

    public function test_reset_password_fails_with_invalid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid token']);
    }
}
