<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;
use Defuse\Crypto\Key;

/**
 * Tests for src/bots/crypt_helps.php
 *
 * Covers:
 *   - encode_value
 *   - decode_value
 */
class CryptHelpsTest extends TestCase
{
    /** A real Defuse key used throughout the test suite */
    private static Key $cookieKey;
    private static Key $decryptKey;

    public static function setUpBeforeClass(): void
    {
        // Generate fresh random keys; we inject them via $GLOBALS so that the
        // global variables set by config.php are available to crypt_helps.php.
        self::$cookieKey  = Key::createNewRandomKey();
        self::$decryptKey = Key::createNewRandomKey();

        // crypt_helps.php reads $cookie_key / $decrypt_key from the global scope.
        $GLOBALS['cookie_key']  = self::$cookieKey;
        $GLOBALS['decrypt_key'] = self::$decryptKey;
    }

    protected function setUp(): void
    {
        // Ensure
    }

    // -------------------------------------------------------------------------
    // encode_value / decode_value – cookie key (default)
    // -------------------------------------------------------------------------

    public function testEncodeDecodeCookieKey(): void
    {
        $plain     = 'super-secret-token';
        $encrypted = \Publish\CryptHelps\encode_value($plain);
        $this->assertNotEmpty($encrypted);
        $this->assertNotSame($plain, $encrypted);

        $decrypted = \Publish\CryptHelps\decode_value($encrypted);
        $this->assertSame($plain, $decrypted);
    }

    public function testEncodeDecodeCookieKeyExplicit(): void
    {
        $plain     = 'another-token';
        $encrypted = \Publish\CryptHelps\encode_value($plain, 'cookie');
        $decrypted = \Publish\CryptHelps\decode_value($encrypted, 'cookie');
        $this->assertSame($plain, $decrypted);
    }

    // -------------------------------------------------------------------------
    // encode_value / decode_value – decrypt key
    // -------------------------------------------------------------------------

    public function testEncodeDecodeDecryptKey(): void
    {
        $plain     = 'access-secret-value';
        $encrypted = \Publish\CryptHelps\encode_value($plain, 'decrypt');
        $this->assertNotEmpty($encrypted);

        $decrypted = \Publish\CryptHelps\decode_value($encrypted, 'decrypt');
        $this->assertSame($plain, $decrypted);
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testEncodeEmptyStringReturnsEmpty(): void
    {
        $result = \Publish\CryptHelps\encode_value('');
        $this->assertSame('', $result);
    }

    public function testEncodeWhitespaceOnlyReturnsEmpty(): void
    {
        $result = \Publish\CryptHelps\encode_value('   ');
        $this->assertSame('', $result);
    }

    public function testDecodeEmptyStringReturnsEmpty(): void
    {
        $result = \Publish\CryptHelps\decode_value('');
        $this->assertSame('', $result);
    }

    public function testDecodeWhitespaceOnlyReturnsEmpty(): void
    {
        $result = \Publish\CryptHelps\decode_value('   ');
        $this->assertSame('', $result);
    }

    public function testDecodeInvalidCiphertextReturnsEmpty(): void
    {
        $result = \Publish\CryptHelps\decode_value('this-is-not-valid-ciphertext');
        $this->assertSame('', $result);
    }

    public function testDecodeWithWrongKeyReturnsEmpty(): void
    {
        // Encrypt with cookie key, try to decrypt with decrypt key
        $encrypted = \Publish\CryptHelps\encode_value('some-value', 'cookie');
        $result    = \Publish\CryptHelps\decode_value($encrypted, 'decrypt');
        $this->assertSame('', $result);
    }

    public function testEncodedValuesAreUniquePerCall(): void
    {
        // Defuse uses random IVs – same plaintext must give different ciphertext
        $enc1 = \Publish\CryptHelps\encode_value('same-value');
        $enc2 = \Publish\CryptHelps\encode_value('same-value');
        $this->assertNotSame($enc1, $enc2);
    }


    public function testDecodeValueWithEmptyString(): void
    {
        $result = \Publish\CryptHelps\decode_value("", "cookie");
        $this->assertEquals("", $result);
    }

    public function testDecodeValueWithWhitespaceOnly(): void
    {
        $result = \Publish\CryptHelps\decode_value("   ", "cookie");
        $this->assertEquals("", $result);
    }

    public function testEncodeValueWithEmptyString(): void
    {
        $result = \Publish\CryptHelps\encode_value("", "cookie");
        $this->assertEquals("", $result);
    }

    public function testEncodeValueWithWhitespaceOnly(): void
    {
        $result = \Publish\CryptHelps\encode_value("   ", "cookie");
        $this->assertEquals("", $result);
    }
}
