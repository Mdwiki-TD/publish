<?php

namespace Publish\CryptHelps;
/*
Usage:
use function Publish\CryptHelps\encode_value;
use function Publish\CryptHelps\decode_value;
*/

use Defuse\Crypto\Crypto;


function decode_value($value)
{
    global $decrypt_key;

    if (empty(trim($value))) return "";

    $key = $decrypt_key ?: $GLOBALS['decrypt_key'];
    try {
        return Crypto::decrypt($value, $key);
    } catch (\Exception $e) {
        return "";
    }
}

function encode_value($value)
{
    global $decrypt_key;
    if (empty(trim($value))) return "";

    $key = $decrypt_key ?: $GLOBALS['decrypt_key'];
    try {
        return Crypto::encrypt($value, $key);
    } catch (\Exception $e) {
        return "";
    };
}
