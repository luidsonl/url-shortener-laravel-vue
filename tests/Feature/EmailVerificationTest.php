<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertFalse($user->hasVerifiedEmail());

        Mail::assertSent(VerifyEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_can_verify_email_with_valid_signed_url()
    {
        Event::fake();
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email verified successfully']);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_cannot_verify_email_with_invalid_hash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->getJson($url);

        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_email_returns_message()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email already verified']);
    }

    public function test_login_sends_verification_email_if_not_verified()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        Mail::assertSent(VerifyEmail::class);
    }

    public function test_resend_verification_email()
    {
        Mail::fake();
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/email/resend', ['email' => $user->email]);

        $response->assertStatus(200);
        Mail::assertSent(VerifyEmail::class);
    }
}
