<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class DocumentAnalysisTest extends TestCase
{
    public function test_analysis_is_cached_and_retrieved()
    {
        $documentId = 1;
        $cacheKey = "document_analysis_{$documentId}";
        $expectedResult = [
            'key_sections' => 'Section 1, Section 2',
            'critical_items' => 'Item A, Item B'
        ];

        // Simulate caching the result
        Cache::put($cacheKey, $expectedResult, now()->addMinutes(30));

        // Retrieve from cache
        $cachedResult = Cache::get($cacheKey);

        $this->assertEquals($expectedResult, $cachedResult);
    }
}
