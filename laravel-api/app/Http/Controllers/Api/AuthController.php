<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminUser;
use App\Services\JwtService;
use App\Services\EmailService;
use App\Services\AuditService;
use App\Services\CryptoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    private JwtService $jwt;
    private EmailService $email;
    private AuditService $audit;
    private CryptoService $crypto;

    public function __construct(
        JwtService $jwt,
        EmailService $email,
        AuditService $audit,
        CryptoService $crypto
    ) {
        $this->jwt = $jwt;
        $this->email = $email;
        $this->audit = $audit;
        $this->crypto = $crypto;
    }

    /**
     * User registration
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'firstName' => 'required|string|min:2|max:50',
            'lastName' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'required|string|min:10|max:15',
            'password' => 'required|string|min:6',
        ]);

        // Check email uniqueness
        $existing = User::where('email', $data['email'])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'Bu e-posta adresi zaten kayıtlı',
                'code' => 'EMAIL_EXISTS',
            ], 409);
        }

        // Phone format validation
        $phone = preg_replace('/[\s\-\(\)]/', '', $data['phone']);
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            return response()->json([
                'success' => false,
                'error' => 'Geçersiz telefon formatı',
            ], 400);
        }

        // Create user
        $user = new User();
        $user->id = Uuid::uuid4()->toString();
        $user->first_name = $data['firstName'];
        $user->last_name = $data['lastName'];
        $user->email = $data['email'];
        $user->phone = $phone;
        $user->password_hash = Hash::make($data['password']);
        $user->auth_provider = 'email';
        $user->save();

        // Generate token
        $token = $this->jwt->generateUserToken([
            'id' => $user->id,
            'email' => $user->email,
        ]);

        // Send welcome email (async - don't block response)
        try {
            $this->email->sendWelcome([
                'id' => $user->id,
                'first_name' => $user->first_name,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            // Log but don't fail
        }

        // Audit log
        $this->audit->logFromRequest(
            AuditService::USER_CREATE,
            $user->id,
            'user',
            $user->id,
            ['email' => $user->email]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->toApiArray(),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * User login
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            $this->audit->logFromRequest(
                AuditService::USER_LOGIN_FAILED,
                null,
                'user',
                null,
                ['email' => $data['email']]
            );

            return response()->json([
                'success' => false,
                'error' => 'E-posta veya şifre hatalı',
            ], 401);
        }

        $token = $this->jwt->generateUserToken([
            'id' => $user->id,
            'email' => $user->email,
        ]);

        $this->audit->logFromRequest(
            AuditService::USER_LOGIN,
            $user->id,
            'user',
            $user->id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->toApiArray(),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Admin login
     * POST /api/admin/login
     */
    public function adminLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::where('username', $data['username'])->first();

        if (!$admin || !Hash::check($data['password'], $admin->password_hash)) {
            $this->audit->logFromRequest(
                AuditService::ADMIN_LOGIN_FAILED,
                null,
                'admin',
                null,
                ['username' => $data['username']]
            );

            return response()->json([
                'success' => false,
                'error' => 'Kullanıcı adı veya şifre hatalı',
            ], 401);
        }

        $token = $this->jwt->generateAdminToken([
            'id' => $admin->id,
            'username' => $admin->username,
        ]);

        $this->audit->logFromRequest(
            AuditService::ADMIN_LOGIN,
            $admin->id,
            'admin',
            $admin->id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'username' => $admin->username,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Google OAuth initiate
     * GET /api/auth/google
     */
    public function googleAuth(Request $request): JsonResponse
    {
        // Get OAuth settings
        $settings = DB::table('oauth_settings')
            ->where('provider', 'google')
            ->first();

        if (!$settings || !$settings->enabled) {
            return response()->json([
                'success' => false,
                'error' => 'Google ile giriş şu an kullanılamıyor',
                'code' => 'oauth_disabled',
            ], 400);
        }

        if (!$settings->client_id || !$settings->client_secret) {
            return response()->json([
                'success' => false,
                'error' => 'Google OAuth yapılandırılmamış',
                'code' => 'oauth_not_configured',
            ], 400);
        }

        try {
            $clientId = $this->crypto->decrypt($settings->client_id);
            $baseUrl = config('app.url');
            $redirectUri = $baseUrl . '/api/auth/google/callback';

            // Generate CSRF state
            $state = bin2hex(random_bytes(16));
            session(['google_oauth_state' => $state]);

            $params = http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'access_type' => 'offline',
                'state' => $state,
                'prompt' => 'select_account',
            ]);

            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;

            return response()->json([
                'success' => true,
                'data' => ['authUrl' => $authUrl],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'OAuth yapılandırma hatası',
                'code' => 'oauth_config_error',
            ], 500);
        }
    }

    /**
     * Google OAuth callback
     * GET /api/auth/google/callback
     */
    public function googleCallback(Request $request)
    {
        $baseUrl = config('app.url');
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        if ($error) {
            return redirect($baseUrl . '?error=google_auth_denied');
        }

        if (!$code) {
            return redirect($baseUrl . '?error=invalid_callback');
        }

        // Verify state (CSRF protection)
        $savedState = session('google_oauth_state');
        if (!$savedState || $state !== $savedState) {
            return redirect($baseUrl . '?error=invalid_state');
        }
        session()->forget('google_oauth_state');

        try {
            // Get OAuth settings
            $settings = DB::table('oauth_settings')
                ->where('provider', 'google')
                ->first();

            $clientId = $this->crypto->decrypt($settings->client_id);
            $clientSecret = $this->crypto->decrypt($settings->client_secret);
            $redirectUri = $baseUrl . '/api/auth/google/callback';

            // Exchange code for token
            $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                return redirect($baseUrl . '?error=token_exchange_failed');
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'];

            // Get user info
            $userInfoResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (!$userInfoResponse->successful()) {
                return redirect($baseUrl . '?error=user_info_failed');
            }

            $googleUser = $userInfoResponse->json();

            // Find or create user
            $user = User::where('google_id', $googleUser['id'])->first();

            if (!$user) {
                $user = User::where('email', $googleUser['email'])->first();

                if ($user) {
                    // Link existing account
                    $user->google_id = $googleUser['id'];
                    $user->avatar_url = $googleUser['picture'] ?? null;
                    $user->save();
                } else {
                    // Create new user
                    $user = new User();
                    $user->id = Uuid::uuid4()->toString();
                    $user->first_name = $googleUser['given_name'] ?? 'User';
                    $user->last_name = $googleUser['family_name'] ?? '';
                    $user->email = $googleUser['email'];
                    $user->google_id = $googleUser['id'];
                    $user->avatar_url = $googleUser['picture'] ?? null;
                    $user->auth_provider = 'google';
                    $user->password_hash = Hash::make(bin2hex(random_bytes(16))); // Random password
                    $user->save();

                    // Send welcome email
                    try {
                        $this->email->sendWelcome([
                            'id' => $user->id,
                            'first_name' => $user->first_name,
                            'email' => $user->email,
                        ]);
                    } catch (\Exception $e) {
                        // Log but don't fail
                    }
                }
            }

            // Generate JWT token
            $token = $this->jwt->generateUserToken([
                'id' => $user->id,
                'email' => $user->email,
            ]);

            // Set cookies for frontend to read
            $userData = json_encode($user->toApiArray());

            return redirect($baseUrl . '?google_auth=success')
                ->cookie('googleAuthToken', $token, 5, '/', null, true, false)
                ->cookie('googleAuthUser', urlencode($userData), 5, '/', null, true, false);

        } catch (\Exception $e) {
            return redirect($baseUrl . '?error=oauth_callback_error');
        }
    }

    /**
     * Get current user profile
     * GET /api/account/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');
        $user = User::find($authUser['id']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Kullanıcı bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user->toApiArray(),
        ]);
    }

    /**
     * Update user profile
     * PUT /api/account/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');
        $user = User::find($authUser['id']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Kullanıcı bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'firstName' => 'sometimes|string|min:2|max:50',
            'lastName' => 'sometimes|string|min:2|max:50',
            'phone' => 'sometimes|string|min:10|max:15',
        ]);

        if (isset($data['firstName'])) {
            $user->first_name = $data['firstName'];
        }
        if (isset($data['lastName'])) {
            $user->last_name = $data['lastName'];
        }
        if (isset($data['phone'])) {
            $user->phone = preg_replace('/[\s\-\(\)]/', '', $data['phone']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'data' => $user->toApiArray(),
        ]);
    }

    /**
     * Change password
     * PUT /api/account/password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');
        $user = User::find($authUser['id']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Kullanıcı bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
            'confirmPassword' => 'required|string',
        ]);

        if ($data['newPassword'] !== $data['confirmPassword']) {
            return response()->json([
                'success' => false,
                'error' => 'Şifreler eşleşmiyor',
            ], 400);
        }

        if (!Hash::check($data['currentPassword'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'error' => 'Mevcut şifre hatalı',
            ], 400);
        }

        $user->password_hash = Hash::make($data['newPassword']);
        $user->save();

        // Send notification email
        try {
            $this->email->sendPasswordChanged([
                'id' => $user->id,
                'first_name' => $user->first_name,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            // Log but don't fail
        }

        return response()->json([
            'success' => true,
            'message' => 'Şifre başarıyla değiştirildi',
        ]);
    }

    /**
     * Get current user (alias for profile)
     * GET /api/account/me
     */
    public function me(Request $request): JsonResponse
    {
        return $this->profile($request);
    }

    /**
     * Update current user (alias for updateProfile)
     * PUT /api/account/me
     */
    public function updateMe(Request $request): JsonResponse
    {
        return $this->updateProfile($request);
    }

    /**
     * Delete user account
     * DELETE /api/account/me
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');
        $user = User::find($authUser['id']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Kullanıcı bulunamadı',
            ], 404);
        }

        // Check for pending orders
        $pendingOrders = DB::table('orders')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingOrders > 0) {
            return response()->json([
                'success' => false,
                'error' => 'Bekleyen siparişleriniz var. Hesabı silebilmek için önce siparişlerin tamamlanmasını bekleyin.',
            ], 400);
        }

        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hesabınız başarıyla silindi',
        ]);
    }

    /**
     * Get Google OAuth status
     * GET /api/auth/google/status
     */
    public function googleStatus(Request $request): JsonResponse
    {
        $settings = DB::table('oauth_settings')
            ->where('provider', 'google')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => (bool) ($settings?->enabled ?? false),
                'configured' => $settings && $settings->client_id && $settings->client_secret,
            ],
        ]);
    }
}