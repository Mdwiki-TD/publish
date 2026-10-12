<?php

declare(strict_types=1);

namespace Tests\Publish;

use Defuse\Crypto\Key;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\Settings;

#[CoversClass(Settings::class)]
final class SettingsTest extends TestCase
{
    public function testGetInstanceReturnsSameInstance(): void
    {
        $settings1 = Settings::getInstance();
        $settings2 = Settings::getInstance();

        $this->assertSame($settings1, $settings2);
    }

    public function testEnvironmentHelpers(): void
    {
        $settings = Settings::getInstance();

        $this->assertTrue($settings->isTesting());
        $this->assertFalse($settings->isProduction());
        $this->assertFalse($settings->isDevelopment());
    }

    public function testMagicGetReturnsPropertyAndSetThrowsException(): void
    {
        $settings = Settings::getInstance();

        $this->assertSame('mdwiki MediaWiki OAuth Client/1.0', $settings->userAgent);
        $this->assertSame('https://meta.wikimedia.org/w/index.php?title=Special:OAuth', $settings->oauthUrl);
        $this->assertSame('https://meta.wikimedia.org/w/api.php', $settings->apiUrl);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Settings are read-only');
        /** @phpstan-ignore-next-line */
        $settings->nonExistentSetProperty = 'invalid';
    }

    public function testMagicGetNonExistentPropertyThrowsException(): void
    {
        $settings = Settings::getInstance();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Undefined setting: nonexistentProperty');
        /** @phpstan-ignore-next-line */
        $null = $settings->nonexistentProperty;
    }

    public function testGenerateCallbackUrl(): void
    {
        $settings = Settings::getInstance();
        $url = $settings->generateCallbackUrl('test/callback.php');

        $this->assertStringEndsWith('/test/callback.php', $url);
        $this->assertStringStartsWith('http', $url);
    }

    public function testGetKeyAndCryptoEncodeDecode(): void
    {
        $settings = Settings::getInstance();

        $key = $settings->getKey('crypt');
        $this->assertInstanceOf(Key::class, $key);

        $plainText = 'Hello World Secret 123';
        $encoded = $settings->encodeValue($plainText, $key);
        $this->assertNotEmpty($encoded);
        $this->assertNotEquals($plainText, $encoded);

        $decoded = $settings->decodeValue($encoded, $key);
        $this->assertSame($plainText, $decoded);
    }

    public function testEncodeAndDecodeValueWithNullKeyOrEmptyValue(): void
    {
        $settings = Settings::getInstance();

        $this->assertSame('', $settings->encodeValue('test', null));
        $this->assertSame('', $settings->encodeValue('', $settings->cryptKey));
        $this->assertSame('', $settings->decodeValue('invalid_base64', null));
        $this->assertSame('', $settings->decodeValue('', $settings->cryptKey));
        $this->assertSame('', $settings->decodeValue('corrupted_data', $settings->cryptKey));
    }

    public function testUnserializeSingletonThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot unserialize a singleton');
        unserialize('O:16:"Publish\Settings":0:{}');
    }
}
