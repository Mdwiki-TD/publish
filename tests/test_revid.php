<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../bots/helps.php';
include_once __DIR__ . '/../bots/revids_bot.php';

use function Publish\Revids\get_revid_db;
use function Publish\Revids\get_revid;

$title = "Lyme disease";

$revid_json = get_revid($title);

echo "revid_json: $revid_json";

$revid_db = get_revid_db($title);

echo "revid_db: $revid_db";
