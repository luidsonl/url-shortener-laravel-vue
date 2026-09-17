<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtService implements AuthServiceInterface
{
    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials', 401);
        }

        $token = JWTAuth::fromUser($user);

        return $this->createTokenForUser($user, $token);
    }

    public function createTokenForUser(User $user, $token = null)
    {
        if (!$token) {
            $token = JWTAuth::fromUser($user);
        }

        $payload = JWTAuth::setToken($token)->getPayload();
        $expiresIn = $payload->get('exp') - time();

        return [
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $expiresIn,
            'user' => $user
        ];
    }

    public function logout()
    {
        JWTAuth::parseToken()->invalidate();
        return ['message' => 'Logout successful'];
    }

    public function user()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateToken($token)
    {
        try {
            return JWTAuth::setToken($token)->authenticate();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getTokenType()
    {
        return 'jwt';
    }
}
