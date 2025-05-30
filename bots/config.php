<?php
//---
include_once __DIR__ . '/../../auth/vendor_load.php';
//---
use Defuse\Crypto\Key;
//---
$inifile = 'I:/mdwiki/mdwiki/confs/OAuthConfig.ini';
//---
if (!file_exists($inifile)) {
    // get the root path from __FILE__ , split before public_html
    // split the file path on the public_html directory
    $pathParts = explode('public_html', __FILE__);
    // the root path is the first part of the split file path
    $ROOT_PATH = $pathParts[0];
    //---
    $inifile = $ROOT_PATH . '/confs/OAuthConfig.ini';
    //---
}
$ini = parse_ini_file($inifile);
//---
if ($ini === false) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "The ini file:($inifile) could not be read";
    exit(0);
}
if (
    !isset($ini['agent']) ||
    !isset($ini['consumerKey']) ||
    !isset($ini['consumerSecret'])
) {
    header("HTTP/1.1 500 Internal Server Error");
    echo 'Required configuration directives not found in ini file';
    exit(0);
}
// $gUserAgent = $ini['agent'];
$gUserAgent = 'mdwiki MediaWiki OAuth Client/1.0';
// Load the user token (request or access) from the session
//---
// To get this demo working, you need to go to this wiki and register a new OAuth consumer.
// Not that this URL must be of the long form with 'title=Special:OAuth', and not a clean URL.
$oauthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';

// Make the api.php URL from the OAuth URL.
$apiUrl = preg_replace('/index\.php.*/', 'api.php', $oauthUrl);

// When you register, you will get a consumer key and secret. Put these here (and for real
// applications, keep the secret secret! The key is public knowledge.).
$consumerKey    = $ini['consumerKey'] ?? '';
$consumerSecret = $ini['consumerSecret'] ?? '';

$consumerKey_new    = $ini['consumerKey_new'] ?? '';
$consumerSecrety_new = $ini['consumerSecrety_new'] ?? '';

$domain = $_SERVER['SERVER_NAME'] ?? 'localhost';

$cookie_key     = $ini['cookie_key'] ?? '';
$cookie_key = Key::loadFromAsciiSafeString($cookie_key);

$decrypt_key     = $ini['decrypt_key'] ?? '';
$decrypt_key = Key::loadFromAsciiSafeString($decrypt_key);
