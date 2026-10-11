<?php

declare(strict_types=1);

namespace Tests\Publish\Http;

use PHPUnit\Framework\TestCase;
use Publish\Http\PublishEndpoint;
use Publish\Process\StartController;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PublishEndpoint::class)]
final class PublishEndpointTest extends TestCase
{
    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        putenv('PUBLISH_SECRET_CODE=secret123');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        putenv('PUBLISH_SECRET_CODE=');
    }

    public function testRunExecutesControllerWhenAuthorizedAndPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_SECRET_KEY'] = 'secret123';
        $_POST = ['test' => 'data'];

        $mockController = $this->createMock(StartController::class);
        $mockController->expects($this->once())
            ->method('run')
            ->with(['test' => 'data']);

        $endpoint = new PublishEndpoint($mockController);

        // Capture header output / buffer
        ob_start();
        $endpoint->run();
        ob_end_clean();
    }
}
