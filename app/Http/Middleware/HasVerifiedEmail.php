<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasVerifiedEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->guard()->user();
        
        if (!$user || !$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }
        return $next($request);
    }
}
