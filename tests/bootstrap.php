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

putenv('CONSUMER_KEY=test_consumer_key');
putenv('CONSUMER_SECRET=test_consumer_secret');
putenv('PUBLISH_REPORTS_PATH=' . sys_get_temp_dir() . '/publish_reports_phpunit');

// Provide placeholder keys that satisfy Defuse\Crypto\Key::loadFromAsciiSafeString()
// In real tests you'd generate these with Key::createNewRandomKey()->saveToAsciiSafeString()
// For unit tests that don't call crypto operations directly, empty strings are fine.

putenv('COOKIE_KEY=def000008f0992fd44f7b71bc86a13c50ffa0295fabd0b8b008fc19d75774746ae6ef19e0328d36d9b457496158ae01fa22dc7638759aadf6c45fd4cda76edb865b0222f');
putenv('DECRYPT_KEY=def000001358577eb292b944a354cfe446413d532d4c18c963597a88ec1daeba34080234b36ad1c54269ff04c443b5155c0c122a2c4e95137b12507b924f799bf13d8571');

// putenv('ALL_PAGES_REVIDS_PATH=/tmp/all_pages_revids_test.json');


$_SERVER['SERVER_NAME'] = 'localhost';

include_once dirname(__DIR__) . '/src/su/include.php';
