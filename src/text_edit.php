<?php

namespace Publish\EditProcess;

if (substr(__DIR__, 0, 2) == 'I:') {
    $workFile = 'I:/mdwiki/fix_refs_repo/work.php';
} else {
    $workFile = __DIR__ . '/../fix_refs/work.php';
}
if (file_exists($workFile)) {
    include_once $workFile;
}

function text_changes($sourcetitle, $title, $text, $lang, $mdwiki_revid)
{
    if (function_exists('\WpRefs\FixPage\DoChangesToText1')) {
        $text = \WpRefs\FixPage\DoChangesToText1($sourcetitle, $title, $text, $lang, $mdwiki_revid);
    }
    return $text;
}
