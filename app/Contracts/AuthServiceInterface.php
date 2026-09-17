<?php

namespace App\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    public function login(array $credentials);
    public function logout();
    public function user();
    public function validateToken($token);
    public function createTokenForUser(User $user);
    public function getTokenType();
}