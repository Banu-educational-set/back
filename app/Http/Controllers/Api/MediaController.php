<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\HomeworkSubmission;
use App\Models\Media;
use App\Models\Term;
use App\Models\User;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->mediaService->upload(
            file: $request->file('file'),
            purpose: $request->string('purpose')->toString(),
            uploader: $request->user(),
        );

        return ApiResponse::success(new MediaResource($media), 'Media uploaded.', 201);
    }

    public function destroy(Request $request, Media $medium): JsonResponse
    {
        $user = $request->user();
        $owner = $medium->model;

        if ($owner === null) {
            // Pending uploads can be deleted by the uploader.
            if ($medium->uploaded_by !== $user->id && ! $user->hasRole(RoleName::Admin->value)) {
                return ApiResponse::error('Forbidden.', null, 403);
            }
            $this->mediaService->delete($medium);

            return ApiResponse::success(null, 'Media deleted.');
        }

        $allowed = ($owner->is($user)) || $user->can('update', $owner);

        if (! $allowed) {
            return ApiResponse::error('Forbidden.', null, 403);
        }

        $this->mediaService->delete($medium);

        return ApiResponse::success(null, 'Media deleted.');
    }

    /**
     * Stream a media file to the user, after authorizing them against the
     * specific owning resource (avatar / session / homework / etc.).
     */
    public function download(Request $request, Media $medium): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $owner = $medium->model;

        // Pending uploads (not yet attached to any model): only the uploader
        // and admins can preview/download.
        if ($owner === null) {
            $canAccessPending = $user->hasRole(RoleName::Admin->value)
                || $medium->uploaded_by === $user->id;

            if (! $canAccessPending) {
                return ApiResponse::error('Forbidden.', null, 403);
            }
        } elseif (! $this->canDownload($user, $owner)) {
            return ApiResponse::error('Forbidden.', null, 403);
        }

        if (! Storage::disk($medium->disk)->exists($medium->file_path)) {
            return ApiResponse::error('File missing on disk.', null, 404);
        }

        return Storage::disk($medium->disk)->download($medium->file_path, $medium->file_name);
    }

    private function canDownload(User $user, $owner): bool
    {
        // Admin can download anything.
        if ($user->hasRole(RoleName::Admin->value)) {
            return true;
        }

        return match (true) {
            // User-owned media (avatar): self only.
            $owner instanceof User => $owner->is($user),

            // Homework submission file: the submitting student, or any teacher
            // (review policy).
            $owner instanceof HomeworkSubmission =>
                $owner->user_id === $user->id || $user->can('review', $owner),

            // Session/term/course media: any authenticated user can read these,
            // matching the read access of those resources.
            $owner instanceof CourseSession,
            $owner instanceof Term,
            $owner instanceof Course => true,

            default => false,
        };
    }
}
