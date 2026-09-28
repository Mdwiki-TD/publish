<?php

namespace Publish\CryptHelps;

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

$_decrypt_key_str    = getenv("DECRYPT_KEY") ?: '';
$_encrypt_key_str    = getenv("ENCRYPT_KEY") ?: '';

$cryptKey = $_decrypt_key_str ? Key::loadFromAsciiSafeString($_decrypt_key_str) : null;
$encryptkey = $_encrypt_key_str ? Key::loadFromAsciiSafeString($_encrypt_key_str) : null;

function decode_value($value)
{
    global $cryptKey;

    if (empty(trim($value))) return "";

    $key = $cryptKey ?: $GLOBALS['decrypt_key'];
    try {
        return Crypto::decrypt($value, $key);
    } catch (\Exception $e) {
        return "";
    }
}

function encode_value($value)
{
    global $cryptKey;
    if (empty(trim($value))) return "";

    $key = $cryptKey ?: $GLOBALS['decrypt_key'];
    try {
        return Crypto::encrypt($value, $key);
    } catch (\Exception $e) {
        return "";
    };
}
