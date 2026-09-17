<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_auto_verified_when_smtp_host_is_empty()
    {
        // Mock config: SMTP host is empty
        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => '']);

        $user = User::factory()->create();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_user_is_auto_verified_when_mailer_is_log()
    {
        // Mock config: Mailer is log
        config(['mail.default' => 'log']);
        config(['mail.mailers.smtp.host' => 'smtp.mailtrap.io']); // Host is set but mailer is log

        $user = User::factory()->create();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_user_is_not_auto_verified_when_smtp_host_is_set_and_mailer_is_smtp()
    {
        // Mock config: Valid SMTP
        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => 'smtp.mailtrap.io']);

        $user = User::factory()->create(['email_verified_at' => null]);

        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_registration_does_not_send_verification_email_if_auto_verified()
    {
        // Mock config: Trigger auto-verify
        config(['mail.default' => 'log']);

        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Auto Verified User',
            'email' => 'auto@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'auto@example.com')->first();
        $this->assertNotNull($user->email_verified_at);

        // Ensure no verification mail was queued
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }
}
