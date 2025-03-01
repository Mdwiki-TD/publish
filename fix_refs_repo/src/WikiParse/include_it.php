<?php

foreach (glob(__DIR__ . "/parsewiki/DataModel/*.php") as $filename) {
    include_once $filename;
}
foreach (glob(__DIR__ . "/parsewiki/*.php") as $filename) {
    include_once $filename;
}

// تضمين الملفات الأخرى خارج المجلد
include_once __DIR__ . '/Template.php';
include_once __DIR__ . '/Citations_reg.php';
include_once __DIR__ . '/Citations.php';
include_once __DIR__ . '/Category.php';
