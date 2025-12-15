<?php

declare(strict_types=1);

namespace Tests\Feature;

use Blaspsoft\Onym\Facades\Onym;
use Tests\TestCase;

final class OnymIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_generate_uuid_filename(): void
    {
        $filename = Onym::make(strategy: 'uuid', extension: 'pdf');

        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertEquals(36 + 4, strlen($filename)); // 36 for UUID + 4 for .pdf
    }

    /** @test */
    public function it_can_generate_slug_filename(): void
    {
        $filename = Onym::make('My Test File', 'txt', 'slug');

        $this->assertEquals('my-test-file.txt', $filename);
    }

    /** @test */
    public function it_can_generate_timestamp_filename_with_prefix_suffix(): void
    {
        $filename = Onym::make(
            'doc',
            'log',
            'timestamp',
            [
                'format' => 'Y-m-d',
                'prefix' => 'test_',
                'suffix' => '_end',
            ],
        );

        $date = now()->format('Y-m-d');
        $this->assertEquals("test_{$date}_doc_end.log", $filename);
    }

    /** @test */
    public function it_resolves_facade(): void
    {
        $this->assertInstanceOf(\Blaspsoft\Onym\Onym::class, resolve('onym'));
    }
}
