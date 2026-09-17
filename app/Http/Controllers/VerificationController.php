<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Mail\VerifyEmail;
use App\Jobs\ProcessEmail;
use Illuminate\Support\Facades\URL;

class VerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }

        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired URL'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully'], 200);
    }

    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        $verifyEmail = new VerifyEmail($url);
        ProcessEmail::dispatch($user, $verifyEmail);

        return response()->json(['message' => 'Verification link sent'], 200);
    }
}