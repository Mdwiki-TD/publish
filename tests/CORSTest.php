<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

include_once dirname(__DIR__) . '/src/su/cors.php';

final class CORSTest extends TestCase
{
    private function setOrigin(string $origin): void
    {
        $_SERVER['HTTP_ORIGIN'] = $origin;
    }

    private function setReferer(string $referer): void
    {
        $_SERVER['HTTP_REFERER'] = $referer;
    }

    private function clearHeaders(): void
    {
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_REFERER']);
    }

    protected function tearDown(): void
    {
        $this->clearHeaders();
    }

    public function testIsAllowedWithAllowedOrigin(): void
    {
        $this->setOrigin('https://medwiki.toolforge.org');
        $result = \Publish\CORS\is_allowed();
        $this->assertEquals('medwiki.toolforge.org', $result);
    }

    public function testIsAllowedWithAllowedReferer(): void
    {
        $this->clearHeaders();
        $this->setReferer('https://mdwikicx.toolforge.org/some/path');
        $result = \Publish\CORS\is_allowed();
        $this->assertEquals('mdwikicx.toolforge.org', $result);
    }

    public function testIsAllowedWithNonAllowedOrigin(): void
    {
        $this->clearHeaders();
        $this->setOrigin('https://example.com');
        $result = \Publish\CORS\is_allowed();
        $this->assertFalse($result);
    }

    public function testIsAllowedWithEmptyOriginAndReferer(): void
    {
        $this->clearHeaders();
        $result = \Publish\CORS\is_allowed();
        $this->assertFalse($result);
    }

    public function testIsAllowedWithPartialDomainMatch(): void
    {
        $this->clearHeaders();
        $this->setOrigin('https://subdomain.medwiki.toolforge.org');
        $result = \Publish\CORS\is_allowed();
        $this->assertEquals('medwiki.toolforge.org', $result);
    }
}
