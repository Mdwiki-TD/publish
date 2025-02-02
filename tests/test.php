<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../bots/Citation.php';

// include_once __DIR__ . '/helps.php';
include_once __DIR__ . '/../bots/md_cat.php';
include_once __DIR__ . '/../bots/text_fix_refs.php';
include_once __DIR__ . '/../bots/remove_duplicate_refs.php';
include_once __DIR__ . '/../bots/text_fix.php';


use function Publish\TextFix\DoChangesToText;

$text = file_get_contents(__DIR__ . '/text.txt');

$new_text = DoChangesToText("title", $text, "fr", "00");

file_put_contents(__DIR__ . '/text_new.txt', $new_text);
