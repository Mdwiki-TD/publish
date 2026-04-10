<?php

namespace Publish\CryptHelps;
/*
Usage:
use function Publish\CryptHelps\encode_value;
use function Publish\CryptHelps\decode_value;
*/

use Defuse\Crypto\Crypto;


function decode_value($value, $key_type = "cookie")
{
    global $cookie_key, $decrypt_key;

    if (empty(trim($value))) return "";

    $use_key = ($key_type == "decrypt") ? $decrypt_key : $cookie_key;
    try {
        return Crypto::decrypt($value, $use_key);
    } catch (\Exception $e) {
        return "";
    }
}

function encode_value($value, $key_type = "cookie")
{
    global $cookie_key, $decrypt_key;
    if (empty(trim($value))) return "";

    $use_key = ($key_type == "decrypt") ? $decrypt_key : $cookie_key;

    try {
        return Crypto::encrypt($value, $use_key);
    } catch (\Exception $e) {
        return "";
    };
}
