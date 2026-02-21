<?php

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';

if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Don't load include.php in tests as it tries to connect to the database
// Instead, we'll load only the specific files we need for testing

// Set test environment
putenv('APP_ENV=testing');
$_SERVER['SERVER_NAME'] = 'localhost';
