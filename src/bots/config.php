<?php
//---
include_once __DIR__ . '/../vendor_load.php';
//---
$oauthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';

// Make the api.php URL from the OAuth URL.
$apiUrl = preg_replace('/index\.php.*/', 'api.php', $oauthUrl);
$domain = $_SERVER['SERVER_NAME'] ?? 'localhost';

use Defuse\Crypto\Key;

// ----------------
// ----------------
$CONSUMER_KEY        = getenv("CONSUMER_KEY") ?: '';
$CONSUMER_SECRET     = getenv("CONSUMER_SECRET") ?: '';
$_cookie_key_str     = getenv("COOKIE_KEY") ?: '';
$_decrypt_key_str    = getenv("DECRYPT_KEY") ?: '';
// ----------------
// ----------------

if ((empty($CONSUMER_KEY) || empty($CONSUMER_SECRET)) && getenv("APP_ENV") === "production") {
    header("HTTP/1.1 500 Internal Server Error");
    error_log("Required configuration directives not found in environment variables!");
    echo 'Required configuration directives not found';
    exit(0);
}

$cookie_key  = $_cookie_key_str ? Key::loadFromAsciiSafeString($_cookie_key_str) : null;
$decrypt_key = $_decrypt_key_str ? Key::loadFromAsciiSafeString($_decrypt_key_str) : null;
