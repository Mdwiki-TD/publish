<?PHP
// src/token.php

use Publish\Http\CxTokenEndpoint;

include_once __DIR__ . '/app/include.php';

(new CxTokenEndpoint())->run();
