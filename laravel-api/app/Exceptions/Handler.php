<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // JSON responses for API
        $this->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Geçersiz veri',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $this->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kimlik doğrulama gerekli',
                ], 401);
            }
        });

        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kaynak bulunamadı',
                ], 404);
            }
        });

        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api')) {
                return response()->json([
                    'success' => false,
                    'error' => 'HTTP metodu desteklenmiyor',
                ], 405);
            }
        });
    }
}