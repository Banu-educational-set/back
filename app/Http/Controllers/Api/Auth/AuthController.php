<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RequestLoginOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Http\Requests\Auth\VerifyPasswordResetOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\Media;
use App\Services\AuthService;
use App\Services\MediaService;
use Illuminate\Support\Arr;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly MediaService $mediaService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success([
            'user' => (new UserResource($result['user']))->resolve($request),
            'token' => $result['token'],
        ], 'Registered.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success([
            'user' => (new UserResource($result['user']))->resolve($request),
            'token' => $result['token'],
        ], 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new UserResource($request->user()->load(['roles', 'avatar', 'province', 'city'])),
            'OK',
        );
    }

    public function updateMe(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill(Arr::only($validated, ['name', 'email', 'phone', 'password', 'province_id', 'city_id']))->save();

        if ($mediaId = Arr::get($validated, 'avatar_media_id')) {
            $this->mediaService->attachTo(
                media: Media::findOrFail((int) $mediaId),
                owner: $user,
                uploader: $user,
                collection: 'avatar',
                singleFile: true,
            );
        }

        return ApiResponse::success(
            new UserResource($user->fresh(['roles', 'avatar', 'province', 'city'])),
            'Profile updated.',
        );
    }

    public function requestLoginOtp(RequestLoginOtpRequest $request): JsonResponse
    {
        $this->authService->requestLoginOtp($request->validated()['phone']);

        return ApiResponse::success(null, 'If the phone is registered, a code has been sent.');
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authService->loginWithOtp(
            $data['phone'],
            $data['code'],
            $data['device_name'] ?? null,
        );

        return ApiResponse::success([
            'user' => (new UserResource($result['user']))->resolve($request),
            'token' => $result['token'],
        ], 'Logged in.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->requestPasswordResetOtp($request->validated()['phone']);

        return ApiResponse::success(null, 'If the phone is registered, a code has been sent.');
    }

    public function verifyPasswordResetOtp(VerifyPasswordResetOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $token = $this->authService->verifyPasswordResetOtp($data['phone'], $data['code']);

        return ApiResponse::success(['reset_token' => $token], 'OTP verified. Use this token to set a new password.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->authService->resetPassword($data['phone'], $data['token'], $data['password']);

        return ApiResponse::success(null, 'Password updated.');
    }
}
