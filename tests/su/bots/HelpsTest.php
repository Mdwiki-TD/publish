<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;
use Defuse\Crypto\Key;

/**
 * Tests for src/bots/helps.php
 *
 * Covers:
 *   - pub_test_print()  – only prints when ?test is in REQUEST
 *   - encode_value()    – encrypts with cookie or decrypt key
 *   - decode_value()    – decrypts with matching key
 *   - get_url_curl()    – makes an HTTP GET and returns the body
 */
class HelpsTest extends TestCase
{
    /** A real Defuse key used throughout the test suite */
    private static Key $cookieKey;
    private static Key $decryptKey;

    public static function setUpBeforeClass(): void
    {
        // Generate fresh random keys; we inject them via $GLOBALS so that the
        // global variables set by config.php are available to helps.php.
        self::$cookieKey  = Key::createNewRandomKey();
        self::$decryptKey = Key::createNewRandomKey();

        // helps.php reads $cookie_key / $decrypt_key from the global scope.
        $GLOBALS['cookie_key']  = self::$cookieKey;
        $GLOBALS['decrypt_key'] = self::$decryptKey;

        // require_once dirname(dirname(__DIR__)) . '/src/su/bots/helps.php';
    }

    protected function setUp(): void
    {
        // Ensure $_REQUEST['test'] is not set by default
        unset($_REQUEST['test']);
    }

    // -------------------------------------------------------------------------
    // pub_test_print
    // -------------------------------------------------------------------------

    public function testPubTestPrintSilentWhenNoTestParam(): void
    {
        ob_start();
        \Publish\Helps\pub_test_print('should-not-appear');
        $output = ob_get_clean();
        $this->assertSame('', $output);
    }

    public function testPubTestPrintOutputsStringWhenTestParam(): void
    {
        $_REQUEST['test'] = '1';
        ob_start();
        \Publish\Helps\pub_test_print('hello world');
        $output = ob_get_clean();
        $this->assertStringContainsString('hello world', $output);
    }

    public function testPubTestPrintOutputsArrayWhenTestParam(): void
    {
        $_REQUEST['test'] = '1';
        ob_start();
        \Publish\Helps\pub_test_print(['key' => 'value']);
        $output = ob_get_clean();
        $this->assertStringContainsString('value', $output);
    }

    // -------------------------------------------------------------------------
    // encode_value / decode_value – cookie key (default)
    // -------------------------------------------------------------------------

    public function testEncodeDecodeCookieKey(): void
    {
        $plain     = 'super-secret-token';
        $encrypted = \Publish\Helps\encode_value($plain);
        $this->assertNotEmpty($encrypted);
        $this->assertNotSame($plain, $encrypted);

        $decrypted = \Publish\Helps\decode_value($encrypted);
        $this->assertSame($plain, $decrypted);
    }

    public function testEncodeDecodeCookieKeyExplicit(): void
    {
        $plain     = 'another-token';
        $encrypted = \Publish\Helps\encode_value($plain, 'cookie');
        $decrypted = \Publish\Helps\decode_value($encrypted, 'cookie');
        $this->assertSame($plain, $decrypted);
    }

    // -------------------------------------------------------------------------
    // encode_value / decode_value – decrypt key
    // -------------------------------------------------------------------------

    public function testEncodeDecodeDecryptKey(): void
    {
        $plain     = 'access-secret-value';
        $encrypted = \Publish\Helps\encode_value($plain, 'decrypt');
        $this->assertNotEmpty($encrypted);

        $decrypted = \Publish\Helps\decode_value($encrypted, 'decrypt');
        $this->assertSame($plain, $decrypted);
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testEncodeEmptyStringReturnsEmpty(): void
    {
        $result = \Publish\Helps\encode_value('');
        $this->assertSame('', $result);
    }

    public function testEncodeWhitespaceOnlyReturnsEmpty(): void
    {
        $result = \Publish\Helps\encode_value('   ');
        $this->assertSame('', $result);
    }

    public function testDecodeEmptyStringReturnsEmpty(): void
    {
        $result = \Publish\Helps\decode_value('');
        $this->assertSame('', $result);
    }

    public function testDecodeWhitespaceOnlyReturnsEmpty(): void
    {
        $result = \Publish\Helps\decode_value('   ');
        $this->assertSame('', $result);
    }

    public function testDecodeInvalidCiphertextReturnsEmpty(): void
    {
        $result = \Publish\Helps\decode_value('this-is-not-valid-ciphertext');
        $this->assertSame('', $result);
    }

    public function testDecodeWithWrongKeyReturnsEmpty(): void
    {
        // Encrypt with cookie key, try to decrypt with decrypt key
        $encrypted = \Publish\Helps\encode_value('some-value', 'cookie');
        $result    = \Publish\Helps\decode_value($encrypted, 'decrypt');
        $this->assertSame('', $result);
    }

    public function testEncodedValuesAreUniquePerCall(): void
    {
        // Defuse uses random IVs – same plaintext must give different ciphertext
        $enc1 = \Publish\Helps\encode_value('same-value');
        $enc2 = \Publish\Helps\encode_value('same-value');
        $this->assertNotSame($enc1, $enc2);
    }

    // -------------------------------------------------------------------------
    // get_url_curl – integration / network (skipped in offline CI)
    // -------------------------------------------------------------------------

    /**
     * @group network
     */
    public function testGetUrlCurlReturnsContent(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension not available.');
        }

        $result = \Publish\Helps\get_url_curl('https://httpbin.org/get');
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
    }
}
