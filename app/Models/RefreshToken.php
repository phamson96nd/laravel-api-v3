<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'rotated_from',
        'revoked',
        'device_fingerprint',
        'expires_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createRefreshToken($user, $deviceFingerprint = null, $rotatedFrom = null)
    {
        $plain = Str::random(64); 
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $plain,
            'rotated_from' => $rotatedFrom,
            'device_fingerprint' => $deviceFingerprint,
            'expires_at' => now()->addMinutes(config('jwt.refresh_ttl')),
        ]);
        return $plain;
    }

    public static function verifyRefreshToken($plainToken, $deviceFingerprint = null)
    {
        $token = RefreshToken::where('token', $plainToken)
            ->where('revoked', false)
            ->first();

        if (!$token) return null;

        if ($deviceFingerprint && $token->device_fingerprint !== $deviceFingerprint) return null;
        if ($token->isExpired()) return null;

        return $token;
    }
    
    public static function revokeToken(RefreshToken $token)
    {
        $token->update(['revoked' => true]);
    }
}
