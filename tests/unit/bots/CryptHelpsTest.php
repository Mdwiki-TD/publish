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
    private static Key $decryptKey;
    private static $previous_decrypt_key = null;

    public static function setUpBeforeClass(): void
    {
        self::$previous_decrypt_key = $GLOBALS['decrypt_key'] ?? null;
        // Generate fresh random keys; we inject them via $GLOBALS so that the
        // global variables set by config.php are available to crypt_helps.php.
        self::$decryptKey = Key::createNewRandomKey();

        // crypt_helps.php reads $decrypt_key from the global scope.
        $GLOBALS['decrypt_key'] = self::$decryptKey;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$previous_decrypt_key !== null) {
            $GLOBALS['decrypt_key'] = self::$previous_decrypt_key;
            return;
        }
        unset($GLOBALS['decrypt_key']);
    }
    protected function setUp(): void
    {
        // Ensure
    }

    // -------------------------------------------------------------------------
    // encode_value / decode_value – decrypt key
    // -------------------------------------------------------------------------

    public function testEncodeDecodeDecryptKey(): void
    {
        $plain     = 'access-secret-value';
        $encrypted = \Publish\CryptHelps\encode_value($plain);
        $this->assertNotEmpty($encrypted);

        $decrypted = \Publish\CryptHelps\decode_value($encrypted);
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

    public function testEncodedValuesAreUniquePerCall(): void
    {
        // Defuse uses random IVs – same plaintext must give different ciphertext
        $enc1 = \Publish\CryptHelps\encode_value('same-value');
        $enc2 = \Publish\CryptHelps\encode_value('same-value');
        $this->assertNotSame($enc1, $enc2);
    }
}
