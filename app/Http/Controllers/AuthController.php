<?php

namespace App\Http\Controllers;

use App\Contracts\AuthServiceInterface;
use App\Http\Resources\UserResource;
use App\Jobs\ProcessEmail;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthServiceInterface $authService
    ) {}

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tokenData = $this->authService->createTokenForUser($user);

        if (! $user->hasVerifiedEmail()) {
            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
            );

            $verifyEmail = new VerifyEmail($url);

            ProcessEmail::dispatch($user, $verifyEmail);
        }

        return response()->json([
            'message' => 'User successfully created. Please check your email to verify your account.',
            ...$tokenData,
            'user' => (new UserResource($user))->resolve(),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            /** @var \App\Models\User|null $user */
            $user = User::where('email', $request->email)->first();

            // Note: In a real app we might want to check password first to avoid enumeration/spam, 
            // but for simplicity and "immediately upon login" requirement:
            if ($user && ! $user->hasVerifiedEmail()) {
                $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
                );

                $verifyEmail = new VerifyEmail($url);

                ProcessEmail::dispatch($user, $verifyEmail);
            }

            $tokenData = $this->authService->login($request->only(['email', 'password']));
            return response()->json([
                ...$tokenData,
                'user' => (new UserResource($user))->resolve(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $result = $this->authService->logout();
        return response()->json($result);
    }

    public function user(Request $request)
    {
        $user = $this->authService->user();
        return response()->json(['user' => (new UserResource($user))->resolve()]);
    }

    public function validateToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $user = $this->authService->validateToken($request->token);

        if (!$user) {
            return response()->json(['valid' => false], 401);
        }

        return response()->json(['valid' => true, 'user' => (new UserResource($user))->resolve()]);
    }
}
