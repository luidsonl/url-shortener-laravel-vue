<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumService implements AuthServiceInterface
{
    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        return $this->createTokenForUser($user);
    }

    public function createTokenForUser(User $user)
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $user->createToken('auth-token')->plainTextToken,
            'user' => $user
        ];
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();
        return ['message' => 'Logout successful'];
    }

    public function user()
    {
        return request()->user();
    }

    public function validateToken($token)
    {
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return false;
        }

        return $accessToken->tokenable;
    }

    public function getTokenType()
    {
        return 'sanctum';
    }
}
