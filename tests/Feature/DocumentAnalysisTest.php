<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAnalysisFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_trigger_document_analysis()
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'analysis result']]]], 200),
        ]);

        $user = User::factory()->create();
        Storage::put('documents/test.pdf', 'dummy content');

        $document = Document::create([
            'user_id' => $user->id,
            'filename' => 'documents/test.pdf',
            'original_name' => 'test.pdf',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->postJson("/documents/{$document->id}/analyze");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Analysis complete.',
        ]);
    }
}
