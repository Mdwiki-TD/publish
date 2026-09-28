<?php

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bootstrap for PHPUnit tests
// Sets up environment variables so no real DB/OAuth is needed

// Set test environment
putenv('APP_ENV=testing');
putenv('WIKIDATA_DOMAIN=test.wikidata.org');

# database informations
// putenv('DB_HOST_TOOLS=localhost:3306');
// putenv('DB_NAME=s54732__mdwikiz');

// putenv('TOOL_TOOLSDB_USER=root');
// putenv('TOOL_TOOLSDB_PASSWORD=root11');

# OAuth keys
putenv('CONSUMER_KEY=test_consumer_key');
putenv('CONSUMER_SECRET=test_consumer_secret');

# paths keys
putenv('TABLES_PATH=I:/MD_TOOLS/MDWIKI_MAIN_REPO/src/public_html/td/Tables');
putenv('PUBLISH_REPORTS_PATH=' . sys_get_temp_dir() . '/publish_reports_phpunit');
putenv('ALL_PAGES_REVIDS_PATH=I:/MD_TOOLS/mdwiki.toolforge.org/PHP_REPOS/publish-repo/php-publish-repo/all_pages_revids.json');
putenv('TEXT_WORK_FILE=I:/MD_TOOLS/mdwiki.toolforge.org/PHP_REPOS/fix_refs_repo/src/work.php');

// Provide placeholder keys that satisfy Defuse\Crypto\Key::loadFromAsciiSafeString()
// In real tests you'd generate these with Key::createNewRandomKey()->saveToAsciiSafeString()
// For unit tests that don't call crypto operations directly, empty strings are fine.

putenv('CRYPTO_KEY=' . \Defuse\Crypto\Key::createNewRandomKey()->saveToAsciiSafeString());

$_SERVER['SERVER_NAME'] = 'localhost';

// Load vendor autoloader
include_once dirname(__DIR__) . '/src/app/include.php';
