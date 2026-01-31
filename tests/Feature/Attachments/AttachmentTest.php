<?php

namespace Tests\Feature\Attachments;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class AttachmentTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_user_can_upload_attachment_to_their_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'original_name',
                    'mime_type',
                    'size',
                ],
            ]);

        $this->assertDatabaseHas('attachments', [
            'request_id' => $request->id,
            'original_name' => 'document.pdf',
        ]);
    }

    public function test_user_cannot_upload_to_another_users_request(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $otherUser = User::factory()->create();
        $request = $this->createRequest($otherUser, null, 'draft');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_upload_to_non_draft_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'pending');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(409);
    }

    public function test_upload_validates_file_type(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $file = UploadedFile::fake()->create('script.exe', 1024);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_validates_file_size(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        // 11MB file (assuming 10MB limit)
        $file = UploadedFile::fake()->create('large.pdf', 11264);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_delete_their_attachment(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $attachment = Attachment::factory()->create([
            'request_id' => $request->id,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/attachments/{$attachment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_user_cannot_delete_another_users_attachment(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $otherUser = User::factory()->create();
        $request = $this->createRequest($otherUser, null, 'draft');

        $attachment = Attachment::factory()->create([
            'request_id' => $request->id,
            'uploaded_by' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/v1/attachments/{$attachment->id}");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_download_attachment(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        Storage::disk('local')->put('attachments/test.pdf', 'test content');

        $attachment = Attachment::factory()->create([
            'request_id' => $request->id,
            'uploaded_by' => $user->id,
            'file_path' => 'attachments/test.pdf',
            'original_name' => 'document.pdf',
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/attachments/{$attachment->id}/download");

        $response->assertOk();
    }
}
