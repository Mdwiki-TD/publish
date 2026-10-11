<?PHP
// src/token.php

use Publish\Http\CxTokenEndpoint;

include_once __DIR__ . '/app/bootstrap.php';

(new CxTokenEndpoint())->run();
