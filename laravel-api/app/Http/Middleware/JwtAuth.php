<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\JwtService;

class JwtAuth
{
    private JwtService $jwt;

    public function __construct(JwtService $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * Handle user authentication
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $this->jwt->extractTokenFromHeader($request->header('Authorization'));
        $user = $this->jwt->verifyUserToken($token);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Giriş yapmalısınız',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        // Attach user to request
        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}