<?php
// src/index.php

use Publish\Http\PublishEndpoint;

include_once __DIR__ . '/app/include.php';

(new PublishEndpoint())->run();
