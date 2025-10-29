<?php

namespace App\Http\Controllers;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();
        $deviceFingerprint = substr($request->header('User-Agent') ?? '', 0, 255);
        $refreshToken = RefreshToken::createRefreshToken($user, $deviceFingerprint);

        return response()->json(
            [
                'access_token' => $token = auth()->attempt($credentials),
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60,
                'refresh_expires_in' => config('jwt.refresh_ttl') * 60,
                'user' => auth()->user(),
            ],
        );
    }

    public function refresh(Request $request)
    {
        $plainToken = $request->bearerToken();
        $deviceFingerprint = substr($request->header('User-Agent') ?? '', 0, 255);
        $token = RefreshToken::verifyRefreshToken($plainToken, $deviceFingerprint);
        if (!$token) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $user = User::find($token->user_id);
        $newAccess = JWTAuth::fromUser($user);
        $newRefresh = RefreshToken::createRefreshToken($user, $deviceFingerprint, $token->id);

        $token->update(['revoked' => true]);

        return response()->json([
            'access_token' => $newAccess,
            'refresh_token' => $newRefresh,
            'expires_in' => Auth::factory()->getTTL() * 60,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $plainToken = $request->bearerToken();
            $user = auth('api')->user();
            // if ($request->input('refresh_token') && $user) {
            if ($plainToken) {
                $token = RefreshToken::verifyRefreshToken($plainToken);
                if ($token) RefreshToken::revokeToken($token);
            }
        } catch (TokenExpiredException $e) {
            $user = null;
        }

        if ($user) {
            auth('api')->logout();
        }
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return response()->json(auth()->user());
    }
}
