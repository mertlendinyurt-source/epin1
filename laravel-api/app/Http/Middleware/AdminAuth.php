<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\JwtService;

class AdminAuth
{
    private JwtService $jwt;

    public function __construct(JwtService $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * Handle admin authentication
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $this->jwt->extractTokenFromHeader($request->header('Authorization'));
        $admin = $this->jwt->verifyAdminToken($token);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'error' => 'Yetkisiz erişim',
            ], 401);
        }

        // Attach admin to request
        $request->attributes->set('auth_admin', $admin);

        return $next($request);
    }
}