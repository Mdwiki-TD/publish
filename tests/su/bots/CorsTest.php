<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/su/cors.php
 *
 * The file declares Publish\CORS\is_allowed(), which checks whether the
 * incoming request originates from one of the whitelisted domains.
 */

class CorsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset superglobals before each test
        unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_ORIGIN']);
    }

    private function loadCors(): void
    {
        // Include only once per process; subsequent includes are no-ops.
        require_once dirname(dirname(__DIR__)) . '/src/su/cors.php';
    }

    // -------------------------------------------------------------------------
    // is_allowed() – allowed domains
    // -------------------------------------------------------------------------

    public function testAllowedWhenRefererIsMedwiki(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://medwiki.toolforge.org/some/path';
        $result = \Publish\CORS\is_allowed();
        $this->assertSame('medwiki.toolforge.org', $result);
    }

    public function testAllowedWhenRefererIsMdwikicx(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://mdwikicx.toolforge.org/page';
        $result = \Publish\CORS\is_allowed();
        $this->assertSame('mdwikicx.toolforge.org', $result);
    }

    public function testAllowedWhenOriginIsMedwiki(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://medwiki.toolforge.org';
        $result = \Publish\CORS\is_allowed();
        $this->assertSame('medwiki.toolforge.org', $result);
    }

    public function testAllowedWhenOriginIsMdwikicx(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://mdwikicx.toolforge.org';
        $result = \Publish\CORS\is_allowed();
        $this->assertSame('mdwikicx.toolforge.org', $result);
    }

    // -------------------------------------------------------------------------
    // is_allowed() – blocked / unknown origins
    // -------------------------------------------------------------------------

    public function testDeniedWhenNoRefererOrOrigin(): void
    {
        $this->loadCors();
        $result = \Publish\CORS\is_allowed();
        $this->assertFalse($result);
    }

    public function testDeniedForRandomReferer(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://evil.example.com/';
        $result = \Publish\CORS\is_allowed();
        $this->assertFalse($result);
    }

    public function testDeniedForRandomOrigin(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://notallowed.org';
        $result = \Publish\CORS\is_allowed();
        $this->assertFalse($result);
    }

    public function testDeniedForEmptyRefererAndOrigin(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = '';
        $_SERVER['HTTP_ORIGIN']  = '';
        $result = \Publish\CORS\is_allowed();
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Origin takes precedence check (both set, origin matches)
    // -------------------------------------------------------------------------

    public function testOriginMatchWhenBothSet(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://evil.example.com/';
        $_SERVER['HTTP_ORIGIN']  = 'https://medwiki.toolforge.org';
        // The function stops at the first domain match – origin check is inside
        // the same loop, so the allowed domain string is returned.
        $result = \Publish\CORS\is_allowed();
        $this->assertNotFalse($result);
    }
}


class CORSTest extends TestCase
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
