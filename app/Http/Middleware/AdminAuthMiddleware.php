<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request has a token
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Authentication required'], 401);
        }

        // In a real app, we would validate the token here
        // For this example, we're just checking if any token exists

        return $next($request);
    }
}
