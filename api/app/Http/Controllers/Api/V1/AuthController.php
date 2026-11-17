<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    /**
     * Register a new user and create their first organization.
     *
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole('org_admin');

        $organization = $this->organizationService->createOrganization($user, [
            'name'     => $request->organization_name,
            'currency' => $request->organization_currency ?? 'USD',
            'timezone' => $request->organization_timezone ?? 'UTC',
            'email'    => $user->email,
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken($request->device_name ?? 'web')->plainTextToken;

        return response()->json([
            'message'      => 'Registration successful. Please verify your email.',
            'user'         => new UserResource($user->load('organizations')),
            'organization' => new OrganizationResource($organization),
            'token'        => $token,
        ], 201);
    }

    /**
     * Authenticate user and return a Sanctum API token.
     *
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken($request->device_name ?? 'web')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user'    => new UserResource($user->load('organizations')),
            'token'   => $token,
        ]);
    }

    /**
     * Revoke the current access token (logout).
     *
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated user.
     *
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('organizations')),
        ]);
    }

    /**
     * Re-send email verification notification.
     *
     * POST /api/v1/auth/email/verification-notification
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }
}
