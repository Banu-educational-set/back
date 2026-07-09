<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks authenticated users whose account isn't fully approved from
 * touching anything except a small profile/auth whitelist. Status meanings:
 *
 *   pending  → registered, phone not OTP-verified yet.
 *   verified → phone confirmed, awaiting admin approval.
 *   approved → full access (passes through unconditionally).
 *   blocked  → account disabled; only allowed to view their own profile
 *              and log out.
 */
class EnsureUserApproved
{
    /**
     * Paths that any authenticated user can hit regardless of status. Stored
     * without the leading "api/" because Request::is() matches against
     * Request::path() which is already prefix-stripped.
     */
    private const ALWAYS_ALLOWED = [
        'api/auth/me',
        'api/auth/logout',
    ];

    /**
     * Paths allowed for pending/verified users (but not blocked). Includes the
     * media upload endpoint so an unapproved user can upload an avatar as part
     * of editing their own profile before approval (attached via PATCH /me).
     */
    private const PROFILE_PATHS = [
        'api/me',
        'api/me/register-data',
        'api/media',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $status = $user->status instanceof UserStatus
            ? $user->status
            : UserStatus::tryFrom((string) $user->status);

        if ($status === UserStatus::Approved) {
            return $next($request);
        }

        $path = $request->path();

        if (in_array($path, self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        if ($status !== UserStatus::Blocked && in_array($path, self::PROFILE_PATHS, true)) {
            return $next($request);
        }

        $message = match ($status) {
            UserStatus::Pending => __('errors.verify_phone'),
            UserStatus::Verified => __('errors.awaiting_approval'),
            UserStatus::Blocked => $user->block_reason
                ? __('errors.account_blocked_reason', ['reason' => $user->block_reason])
                : __('errors.account_blocked'),
            default => __('errors.account_not_approved'),
        };

        return ApiResponse::error(
            $message,
            ['status' => $status?->value, 'block_reason' => $status === UserStatus::Blocked ? $user->block_reason : null],
            403,
        );
    }
}
