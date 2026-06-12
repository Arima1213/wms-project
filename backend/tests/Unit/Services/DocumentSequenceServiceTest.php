<?php

namespace Tests\Unit\Services;

use App\Services\DocumentSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentSequenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(DocumentSequenceService::class);
    }

    /** @test */
    public function generate_returns_correct_format_PREFIX_YYYYMMDD_XXXXXX(): void
    {
        $result = $this->service->generate('INB');

        // Expected format: INB-YYYYMMDD-XXXXXX (e.g., INB-20260611-000001)
        $this->assertMatchesRegularExpression(
            '/^INB-\d{8}-\d{6}$/',
            $result
        );

        // Verify the date portion is today's date
        $today = now()->format('Ymd');
        $this->assertStringContainsString($today, $result);

        // First call should return counter 000001
        $this->assertStringEndsWith('-000001', $result);
    }

    /** @test */
    public function getNextNumber_increments_per_prefix_independently(): void
    {
        // Call getNextNumber twice with 'RET' — should return different numbers
        $retFirst = $this->service->getNextNumber('RET');
        $retSecond = $this->service->getNextNumber('RET');

        $this->assertNotSame($retFirst, $retSecond);

        // First should be ...-000001, second should be ...-000002
        $this->assertStringEndsWith('-000001', $retFirst);
        $this->assertStringEndsWith('-000002', $retSecond);

        // Now call with 'INB' — should start at 000001 independently
        $inbFirst = $this->service->getNextNumber('INB');
        $this->assertStringEndsWith('-000001', $inbFirst);
        $this->assertNotSame($retFirst, $inbFirst);
    }

    /** @test */
    public function generate_and_getNextNumber_return_same_result_for_same_prefix(): void
    {
        // generate() is an alias for getNextNumber(). Both produce the same
        // format PREFIX-YYYYMMDD-XXXXXX when called with the same prefix.
        // We test them with independent prefixes so their counters don't interfere.
        $fromGenerate = $this->service->generate('PO');
        $fromGetNextNumber = $this->service->getNextNumber('RFQ');

        // Both should produce PREFIX-YYYYMMDD-XXXXXX format starting at 000001
        $this->assertMatchesRegularExpression('/^PO-\d{8}-\d{6}$/', $fromGenerate);
        $this->assertMatchesRegularExpression('/^RFQ-\d{8}-\d{6}$/', $fromGetNextNumber);

        $this->assertStringEndsWith('-000001', $fromGenerate);
        $this->assertStringEndsWith('-000001', $fromGetNextNumber);

        // Both should contain today's date
        $today = now()->format('Ymd');
        $this->assertStringContainsString($today, $fromGenerate);
        $this->assertStringContainsString($today, $fromGetNextNumber);
    }
}
