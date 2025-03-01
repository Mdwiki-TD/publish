<?php
include_once __DIR__ . '/test_bot.php';

include_once __DIR__ . '/WikiParse/include_it.php';

include_once __DIR__ . '/mv_dots.php';

foreach (glob(__DIR__ . "/bots/*.php") as $filename) {
    include_once $filename;
}
// include_once __DIR__ . '/bots/remove_duplicate_refs.php';
// include_once __DIR__ . '/bots/expend_refs.php';
// include_once __DIR__ . '/bots/es_months.php';
// include_once __DIR__ . '/bots/es_refs.php';
// include_once __DIR__ . '/bots/fix_pt_months.php';
// include_once __DIR__ . '/bots/txtlib2.php';

include_once __DIR__ . '/sw.php';
include_once __DIR__ . '/md_cat.php';
include_once __DIR__ . '/es.php';
include_once __DIR__ . '/infobox.php';
include_once __DIR__ . '/infobox2.php';
include_once __DIR__ . '/index.php';
