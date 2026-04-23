<?php

// ----------------
// ----------------
$CONSUMER_KEY        = getenv("CONSUMER_KEY") ?: '';
$CONSUMER_SECRET     = getenv("CONSUMER_SECRET") ?: '';
// ----------------
// ----------------

if ((empty($CONSUMER_KEY) || empty($CONSUMER_SECRET)) && getenv("APP_ENV") === "production") {
    header("HTTP/1.1 500 Internal Server Error");
    error_log("Required configuration directives not found in environment variables!");
    echo 'Required configuration directives not found';
    exit(0);
}
