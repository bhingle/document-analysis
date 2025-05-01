<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_document()
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/documents', [
            'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'document' => ['id', 'user_id', 'filename', 'original_name', 'status']
        ]);

        Storage::disk('local')->assertExists('documents/' . basename($response->json('document.filename')));
    }
}
