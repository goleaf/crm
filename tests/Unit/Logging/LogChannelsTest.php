<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use Tests\TestCase;

final class LogChannelsTest extends TestCase
{
    public function test_operational_channels_are_configured_for_daily_rotation(): void
    {
        $channels = [
            'system',
            'auth',
            'api',
            'imports',
            'exports',
            'workflow',
            'slow_queries',
            'backups',
            'email',
            'email_subscriptions_channel',
        ];

        foreach ($channels as $channel) {
            $this->assertSame('daily', config("logging.channels.{$channel}.driver"));
            $this->assertNotEmpty(config("logging.channels.{$channel}.path"));

            $days = config("logging.channels.{$channel}.days");
            $this->assertTrue(is_numeric($days));
            $this->assertGreaterThan(0, (int) $days);
        }
    }
}
