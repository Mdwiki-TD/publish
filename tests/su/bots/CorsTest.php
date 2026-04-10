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
        unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_ORIGIN']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_ORIGIN']);
    }

    private function loadCors(): void
    {
        require_once dirname(dirname(dirname(__DIR__))) . '/src/su/cors.php';
    }

    // -------------------------------------------------------------------------
    // is_allowed() – allowed domains
    // -------------------------------------------------------------------------

    public function testAllowedWhenRefererIsMedwiki(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://medwiki.toolforge.org/some/path';
        $this->assertSame('medwiki.toolforge.org', \Publish\CORS\is_allowed());
    }

    public function testAllowedWhenRefererIsMdwikicx(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://mdwikicx.toolforge.org/page';
        $this->assertSame('mdwikicx.toolforge.org', \Publish\CORS\is_allowed());
    }

    public function testAllowedWhenOriginIsMedwiki(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://medwiki.toolforge.org';
        $this->assertSame('medwiki.toolforge.org', \Publish\CORS\is_allowed());
    }

    public function testAllowedWhenOriginIsMdwikicx(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://mdwikicx.toolforge.org';
        $this->assertSame('mdwikicx.toolforge.org', \Publish\CORS\is_allowed());
    }

    // -------------------------------------------------------------------------
    // is_allowed() – blocked / unknown origins
    // -------------------------------------------------------------------------

    public function testDeniedWhenNoRefererOrOrigin(): void
    {
        $this->loadCors();
        $this->assertFalse(\Publish\CORS\is_allowed());
    }

    public function testDeniedForRandomReferer(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://evil.example.com/';
        $this->assertFalse(\Publish\CORS\is_allowed());
    }

    public function testDeniedForRandomOrigin(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://notallowed.org';
        $this->assertFalse(\Publish\CORS\is_allowed());
    }

    public function testDeniedForEmptyRefererAndOrigin(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = '';
        $_SERVER['HTTP_ORIGIN']  = '';
        $this->assertFalse(\Publish\CORS\is_allowed());
    }

    // -------------------------------------------------------------------------
    // حالات خاصة
    // -------------------------------------------------------------------------

    public function testOriginMatchWhenBothSet(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_REFERER'] = 'https://evil.example.com/';
        $_SERVER['HTTP_ORIGIN']  = 'https://medwiki.toolforge.org';
        $this->assertNotFalse(\Publish\CORS\is_allowed());
    }

    public function testAllowedWithPartialDomainMatch(): void
    {
        $this->loadCors();
        $_SERVER['HTTP_ORIGIN'] = 'https://subdomain.medwiki.toolforge.org';
        $this->assertSame('medwiki.toolforge.org', \Publish\CORS\is_allowed());
    }
}
