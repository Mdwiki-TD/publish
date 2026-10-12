<?php

declare(strict_types=1);

namespace Tests\Publish;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\Logger;

#[CoversClass(Logger::class)]
final class LoggerTest extends TestCase
{
    protected function setUp(): void
    {
        Logger::reset();
    }

    protected function tearDown(): void
    {
        Logger::reset();
    }

    public function testSetSinkCapturesLogMessages(): void
    {
        $logs = [];
        Logger::setSink(function (string $level, string $message) use (&$logs) {
            $logs[] = [$level, $message];
        });

        Logger::info('Info message');
        Logger::warning('Warning message');
        Logger::error('Error message');

        $this->assertCount(3, $logs);
        $this->assertSame(['info', 'Info message'], $logs[0]);
        $this->assertSame(['warning', 'Warning message'], $logs[1]);
        $this->assertSame(['error', 'Error message'], $logs[2]);
    }

    public function testDebugLogsWhenInDevelopmentMode(): void
    {
        putenv('APP_ENV=development');
        Logger::reset();

        $logs = [];
        Logger::setSink(function (string $level, string $message) use (&$logs) {
            $logs[] = [$level, $message];
        });

        Logger::debug(['array' => 'data']);

        $this->assertCount(1, $logs);
        $this->assertSame('debug', $logs[0][0]);
        $this->assertStringContainsString('Array', $logs[0][1]);

        putenv('APP_ENV=testing');
    }

    public function testDebugDoesNotLogWhenInTestingMode(): void
    {
        putenv('APP_ENV=testing');
        Logger::reset();

        $logs = [];
        Logger::setSink(function (string $level, string $message) use (&$logs) {
            $logs[] = [$level, $message];
        });

        Logger::debug('Debug string');

        $this->assertEmpty($logs);
    }
}
