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
 *   4. read the request body (JSON or form-urlencoded)
 *   5. delegate to StartController
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

        // Parse the body only after the request has been authenticated.
        $data = $this->getRequestData();

        $this->controller->run($data);
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
    // Request body helpers
    // ------------------------------------------------------------

    /**
     * Return the request data as an associative array.
     *
     * - application/json                  => decoded from php://input
     * - application/x-www-form-urlencoded => $_POST
     * - multipart/form-data               => $_POST
     *
     * PHP only populates $_POST for form-encoded content types, so a JSON
     * body must be read manually from the raw input stream.
     */
    private function getRequestData(): array
    {
        if ($this->isJsonRequest()) {
            return $this->readJsonBody();
        }

        return $_POST;
    }

    private function isJsonRequest(): bool
    {
        // The header may include a charset, e.g. "application/json; charset=utf-8",
        // so use a substring match instead of strict equality.
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

        return stripos($contentType, 'application/json') !== false;
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            $this->fail(400, 'Empty request body'); // Bad Request
        }

        $data = json_decode($raw, true);

        // json_decode() returns null on invalid JSON, and may return a scalar
        // for valid-but-unexpected input (e.g. "123"). Only accept an object/array.
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            $this->fail(400, 'Invalid JSON body'); // Bad Request
        }

        return $data;
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
