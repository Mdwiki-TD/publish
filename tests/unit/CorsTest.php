<?php
namespace Tests\Bots;

use PHPUnit\Framework\TestCase;
use Publish\Cors;

/**
 * Tests for src/app/cors.php
 *
 * The file declares Publish\Cors\isAllowed(), which checks whether the
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

    // -------------------------------------------------------------------------
    // isAllowed() – allowed domains
    // -------------------------------------------------------------------------

    public function testAllowedWhenRefererIsMedwiki(): void
    {

        $_SERVER['HTTP_REFERER'] = 'https://medwiki.toolforge.org/some/path';
        $this->assertSame('medwiki.toolforge.org', Cors::isAllowed());
    }

    public function testAllowedWhenRefererIsMdwikicx(): void
    {

        $_SERVER['HTTP_REFERER'] = 'https://mdwikicx.toolforge.org/page';
        $this->assertSame('mdwikicx.toolforge.org', Cors::isAllowed());
    }

    public function testAllowedWhenOriginIsMedwiki(): void
    {

        $_SERVER['HTTP_ORIGIN'] = 'https://medwiki.toolforge.org';
        $this->assertSame('medwiki.toolforge.org', Cors::isAllowed());
    }

    public function testAllowedWhenOriginIsMdwikicx(): void
    {

        $_SERVER['HTTP_ORIGIN'] = 'https://mdwikicx.toolforge.org';
        $this->assertSame('mdwikicx.toolforge.org', Cors::isAllowed());
    }

    // -------------------------------------------------------------------------
    // isAllowed() – blocked / unknown origins
    // -------------------------------------------------------------------------

    public function testDeniedWhenNoRefererOrOrigin(): void
    {

        $this->assertFalse(Cors::isAllowed());
    }

    public function testDeniedForRandomReferer(): void
    {

        $_SERVER['HTTP_REFERER'] = 'https://evil.example.com/';
        $this->assertFalse(Cors::isAllowed());
    }

    public function testDeniedForRandomOrigin(): void
    {

        $_SERVER['HTTP_ORIGIN'] = 'https://notallowed.org';
        $this->assertFalse(Cors::isAllowed());
    }

    public function testDeniedForEmptyRefererAndOrigin(): void
    {

        $_SERVER['HTTP_REFERER'] = '';
        $_SERVER['HTTP_ORIGIN']  = '';
        $this->assertFalse(Cors::isAllowed());
    }

    // -------------------------------------------------------------------------
    // special cases
    // -------------------------------------------------------------------------

    public function testOriginMatchWhenBothSet(): void
    {

        $_SERVER['HTTP_REFERER'] = 'https://evil.example.com/';
        $_SERVER['HTTP_ORIGIN']  = 'https://medwiki.toolforge.org';
        $this->assertNotFalse(Cors::isAllowed());
    }

    public function testAllowedWithPartialDomainMatch(): void
    {

        $_SERVER['HTTP_ORIGIN'] = 'https://subdomain.medwiki.toolforge.org';
        $this->assertSame('medwiki.toolforge.org', Cors::isAllowed());
    }
}
