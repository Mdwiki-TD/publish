<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\Http\PublishEndpoint;
use Publish\Process\StartController;

#[CoversClass(PublishEndpoint::class)]
final class PublishEndpointTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_X_SECRET_KEY']);
        putenv('PUBLISH_SECRET_CODE=');
    }

    public function testRunExecutesControllerWhenAuthorizedPostRequest(): void
    {
        putenv('PUBLISH_SECRET_CODE=secret123');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_SECRET_KEY'] = 'secret123';
        $_POST = ['test' => 'data'];

        $mockController = $this->createMock(StartController::class);
        $mockController->expects($this->once())
            ->method('run')
            ->with(['test' => 'data']);

        $endpoint = new PublishEndpoint($mockController);

        @$endpoint->run();
    }

    public function testRunFailsWhenNotPostRequest(): void
    {
        $code = '
            require "vendor/autoload.php";
            require "src/app/bootstrap.php";
            putenv("PUBLISH_SECRET_CODE=secret123");
            $_SERVER["REQUEST_METHOD"] = "GET";
            $_SERVER["HTTP_X_SECRET_KEY"] = "secret123";
            $endpoint = new Publish\Http\PublishEndpoint();
            @$endpoint->run();
        ';

        $output = shell_exec('php -r ' . escapeshellarg($code));
        $this->assertStringContainsString('Only POST requests are allowed', (string) $output);
    }

    public function testRunFailsWhenUnauthorizedKey(): void
    {
        $code = '
            require "vendor/autoload.php";
            require "src/app/bootstrap.php";
            putenv("PUBLISH_SECRET_CODE=secret123");
            $_SERVER["REQUEST_METHOD"] = "POST";
            $_SERVER["HTTP_X_SECRET_KEY"] = "wrong_secret";
            $endpoint = new Publish\Http\PublishEndpoint();
            @$endpoint->run();
        ';

        $output = shell_exec('php -r ' . escapeshellarg($code));
        $this->assertStringContainsString('Access denied', (string) $output);
    }

    public function testRunFailsWhenSecretEnvNotSet(): void
    {
        $code = '
            require "vendor/autoload.php";
            require "src/app/bootstrap.php";
            putenv("PUBLISH_SECRET_CODE=");
            $_SERVER["REQUEST_METHOD"] = "POST";
            $_SERVER["HTTP_X_SECRET_KEY"] = "anything";
            $endpoint = new Publish\Http\PublishEndpoint();
            @$endpoint->run();
        ';

        $output = shell_exec('php -r ' . escapeshellarg($code));
        $this->assertStringContainsString('Access denied', (string) $output);
    }
}
