<?php

declare(strict_types=1);

namespace Tests\Publish;

use PHPUnit\Framework\TestCase;
use Publish\Logger;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Logger::class)]
final class LoggerTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_ENV=testing');
        Logger::reset();
    }

    public function testLoggerCustomSinkCapturesLogs(): void
    {
        $captured = [];
        Logger::setSink(function (string $level, string $message) use (&$captured) {
            $captured[] = [$level, $message];
        });

        Logger::info('Info message');
        Logger::warning('Warning message');
        Logger::error('Error message');

        $this->assertCount(3, $captured);
        $this->assertSame(['info', 'Info message'], $captured[0]);
        $this->assertSame(['warning', 'Warning message'], $captured[1]);
        $this->assertSame(['error', 'Error message'], $captured[2]);
    }

    public function testLoggerDebugWithCustomSink(): void
    {
        putenv('APP_ENV=development');
        Logger::reset();

        $captured = [];
        Logger::setSink(function (string $level, string $message) use (&$captured) {
            $captured[] = [$level, $message];
        });

        Logger::debug('Debug string message');
        Logger::debug(['key' => 'value']);

        $this->assertCount(2, $captured);
        $this->assertSame('debug', $captured[0][0]);
        $this->assertSame('Debug string message', $captured[0][1]);
        $this->assertSame('debug', $captured[1][0]);
        $this->assertStringContainsString('key', $captured[1][1]);
    }

    public function testLoggerResetClearsSink(): void
    {
        $captured = [];
        Logger::setSink(function (string $level, string $message) use (&$captured) {
            $captured[] = [$level, $message];
        });

        Logger::info('First');
        $this->assertCount(1, $captured);

        Logger::reset();
        Logger::info('Second');
        // Since APP_ENV is testing, default write with no sink returns silently
        $this->assertCount(1, $captured);
    }
}
