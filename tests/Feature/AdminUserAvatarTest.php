<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUserAvatarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RoleSeeder::class);

        $this->admin = $this->createUser('09120000001');
        $this->admin->assignRole(RoleName::Admin->value);
    }

    public function test_admin_can_create_a_user_with_an_attached_avatar(): void
    {
        $media = $this->pendingMedia($this->admin, 'avatar', 'pending-avatar.jpg');

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/users', [
            'name' => 'کاربر جدید',
            'phone' => '09120000002',
            'password' => 'password123',
            'roles' => [RoleName::Student->value],
            'avatar_media_id' => $media->id,
        ]);

        $response->assertCreated();

        $user = User::where('phone', '09120000002')->firstOrFail();
        $media->refresh();

        $this->assertSame($user->getMorphClass(), $media->model_type);
        $this->assertSame($user->id, $media->model_id);
        $this->assertSame("users/{$user->id}/avatar/pending-avatar.jpg", $media->file_path);
        Storage::disk('public')->assertExists($media->file_path);
        $this->assertNotNull($response->json('data.avatar_url'));
        $this->assertStringEndsWith(
            "/storage/users/{$user->id}/avatar/pending-avatar.jpg",
            $response->json('data.avatar_url'),
        );
    }

    public function test_admin_can_replace_a_users_avatar(): void
    {
        $user = $this->createUser('09120000003');
        $oldMedia = $this->attachedAvatar($user, $this->admin, 'old-avatar.jpg');
        $newMedia = $this->pendingMedia($this->admin, 'avatar', 'new-avatar.jpg');

        $response = $this->actingAs($this->admin, 'sanctum')->patchJson("/api/admin/users/{$user->id}", [
            'avatar_media_id' => $newMedia->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
        Storage::disk('public')->assertMissing($oldMedia->file_path);

        $newMedia->refresh();
        $this->assertSame($user->getMorphClass(), $newMedia->model_type);
        $this->assertSame($user->id, $newMedia->model_id);
        Storage::disk('public')->assertExists($newMedia->file_path);
        $this->assertStringEndsWith(
            "/storage/users/{$user->id}/avatar/new-avatar.jpg",
            $response->json('data.avatar_url'),
        );
    }

    public function test_admin_cannot_attach_an_avatar_uploaded_by_another_user(): void
    {
        $otherUploader = $this->createUser('09120000004');
        $media = $this->pendingMedia($otherUploader, 'avatar', 'other-avatar.jpg');

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/users', [
            'name' => 'کاربر نامعتبر',
            'phone' => '09120000005',
            'password' => 'password123',
            'avatar_media_id' => $media->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar_media_id');

        $this->assertDatabaseMissing('users', ['phone' => '09120000005']);
        $this->assertTrue($media->fresh()->isPending());
    }

    public function test_admin_cannot_use_a_cover_upload_as_an_avatar(): void
    {
        $media = $this->pendingMedia($this->admin, 'cover', 'cover.jpg');

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/users', [
            'name' => 'کاربر نامعتبر',
            'phone' => '09120000006',
            'password' => 'password123',
            'avatar_media_id' => $media->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar_media_id');

        $this->assertDatabaseMissing('users', ['phone' => '09120000006']);
        $this->assertTrue($media->fresh()->isPending());
    }

    private function createUser(string $phone): User
    {
        return User::factory()->create([
            'phone' => $phone,
            'status' => UserStatus::Approved->value,
        ]);
    }

    private function pendingMedia(User $uploader, string $collection, string $filename): Media
    {
        $path = "pending/{$uploader->id}/{$filename}";
        Storage::disk('public')->put($path, 'image-content');

        return Media::create([
            'collection_name' => $collection,
            'file_name' => $filename,
            'file_path' => $path,
            'mime_type' => 'image/jpeg',
            'file_size' => 13,
            'disk' => 'public',
            'uploaded_by' => $uploader->id,
        ]);
    }

    private function attachedAvatar(User $owner, User $uploader, string $filename): Media
    {
        $path = "users/{$owner->id}/avatar/{$filename}";
        Storage::disk('public')->put($path, 'old-image-content');

        return Media::create([
            'model_type' => $owner->getMorphClass(),
            'model_id' => $owner->id,
            'collection_name' => 'avatar',
            'file_name' => $filename,
            'file_path' => $path,
            'mime_type' => 'image/jpeg',
            'file_size' => 17,
            'disk' => 'public',
            'uploaded_by' => $uploader->id,
        ]);
    }
}
