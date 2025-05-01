<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Document;

class DocumentAttributesTest extends TestCase
{
    public function test_document_attributes_are_set_correctly()
    {
        $document = new Document([
            'filename' => 'documents/test.pdf',
            'original_name' => 'test.pdf',
            'status' => 'pending',
            'user_id' => 1,
        ]);

        $this->assertEquals('documents/test.pdf', $document->filename);
        $this->assertEquals('test.pdf', $document->original_name);
        $this->assertEquals('pending', $document->status);
        $this->assertEquals(1, $document->user_id);
    }
}
