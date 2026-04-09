<?php

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Bootstrap for PHPUnit tests
// Sets up environment variables so no real DB/OAuth is needed

// Set test environment
putenv('APP_ENV=testing');
putenv('DB_HOST_TOOLS=localhost:3306');
putenv('DB_NAME=s54732__mdwiki');
putenv('DB_NAME_NEW=s54732__mdwiki_new');
putenv('TOOL_TOOLSDB_USER=root');
putenv('TOOL_TOOLSDB_PASSWORD=root11');

// putenv('CONSUMER_KEY=test_consumer_key');
// putenv('CONSUMER_SECRET=test_consumer_secret');
// putenv('PUBLISH_REPORTS_PATH=/tmp/publish_reports_test');

// Provide placeholder keys that satisfy Defuse\Crypto\Key::loadFromAsciiSafeString()
// In real tests you'd generate these with Key::createNewRandomKey()->saveToAsciiSafeString()
// For unit tests that don't call crypto operations directly, empty strings are fine.
// putenv('COOKIE_KEY=');
// putenv('DECRYPT_KEY=');

// putenv('ALL_PAGES_REVIDS_PATH=/tmp/all_pages_revids_test.json');


$_SERVER['SERVER_NAME'] = 'localhost';

include_once dirname(__DIR__) . '/src/include.php';
