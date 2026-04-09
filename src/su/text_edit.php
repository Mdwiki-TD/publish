<?php

namespace Publish\EditProcess;

$home = getenv('HOME') ?: ($_SERVER['HOME'] ?? "");

$work_file_path = getenv("TEXT_WORK_FILE") ?: ($_ENV['TEXT_WORK_FILE'] ?? $home . '/public_html/fix_refs/work.php');

if (file_exists($work_file_path)) {
    include_once $work_file_path;
}

function text_changes($sourcetitle, $title, $text, $lang, $mdwiki_revid)
{
    if (function_exists('\WpRefs\FixPage\DoChangesToText1')) {
        $text = \WpRefs\FixPage\DoChangesToText1($sourcetitle, $title, $text, $lang, $mdwiki_revid);
    }
    return $text;
}
