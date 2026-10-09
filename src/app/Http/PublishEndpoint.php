<?php
// src/app/Http/PublishEndpoint.php

namespace Publish\Http;

use Publish\Process\StartController;

/**
 * HTTP entry point of the publish API.
 *
 * Responsibilities:
 *   1. send JSON headers
 *   2. accept POST requests only
 *   3. verify the secret key (X-Secret-Key header)
 *   4. delegate to StartController
 */
class PublishEndpoint
{
    private const SECRET_ENV_NAME = 'PUBLISH_SECRET_CODE';
    private const SECRET_HEADER   = 'HTTP_X_SECRET_KEY';

    private StartController $controller;

    public function __construct(?StartController $controller = null)
    {
        $this->controller = $controller ?? new StartController();
    }

    public function run(): void
    {
        $this->sendJsonHeaders();

        if (! $this->isPostRequest()) {
            $this->fail(405, 'Only POST requests are allowed'); // Method Not Allowed
        }

        if (! $this->isAuthorized()) {
            $this->fail(403, 'Access denied. Invalid or missing secret key.'); // Forbidden
        }

        $this->controller->run($_POST);
    }

    // ------------------------------------------------------------
    // Checks
    // ------------------------------------------------------------

    private function isPostRequest(): bool
    {
        // Check if the request is a POST request
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function isAuthorized(): bool
    {
        $expected = $this->getSecretCode();

        // No secret configured => refuse, rather than silently disabling authentication.
        // Note: this makes a missing PUBLISH_SECRET_CODE a hard failure, so it must be set
        // in every deployed environment.
        if ($expected === '') {
            error_log('PublishEndpoint: ' . self::SECRET_ENV_NAME . ' is not configured; rejecting request');
            return false;
        }

        $received = (string) ($_SERVER[self::SECRET_HEADER] ?? '');

        return hash_equals($expected, $received);
    }

    private function getSecretCode(): string
    {
        $code = getenv(self::SECRET_ENV_NAME);
        if ($code === false) {
            $code = $_ENV[self::SECRET_ENV_NAME] ?? '';
        }
        return (string) $code;
    }

    // ------------------------------------------------------------
    // Response helpers
    // ------------------------------------------------------------

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function fail(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }
}
