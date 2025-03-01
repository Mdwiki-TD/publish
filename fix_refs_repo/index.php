<?php

namespace WpRefs\FixPage;
/*
usage:

use function WpRefs\FixPage\fix_page_here;
use function WpRefs\FixPage\DoChangesToText1;

*/

include_once __DIR__ . '/src/include_files.php';

use function WpRefs\WprefText\fix_page;

function json_load_file($filename)
{
    $content = file_get_contents($filename);
    return json_decode($content, true);
}

// ---
$fixwikirefs = "I:/mdwiki/mdwiki/confs/fixwikirefs.json";
// ---
if (!file_exists($fixwikirefs)) {
    $fixwikirefs = __DIR__ . "/../../../confs/fixwikirefs.json";
}
// ---
$setting = [];
// ---
// التحقق مما إذا كان الملف موجودًا
if (file_exists($fixwikirefs)) {
    try {
        $setting = json_load_file($fixwikirefs);
    } catch (\Exception $e) {
        $setting = [];
    }
}

function fix_page_here($text, $title, $langcode, $sourcetitle, $revid)
{
    global $setting;
    // ---
    $lang_default = isset($setting[$langcode]) ? $setting[$langcode] : [];
    // ---
    $move_dots = isset($lang_default['move_dots']) && $lang_default['move_dots'] == 1;
    $expand = isset($lang_default['expend']) && $lang_default['expend'] == 1;
    $add_en_lang = isset($lang_default['add_en_lang']) && $lang_default['add_en_lang'] == 1;
    // ---
    $text = fix_page($text, $title, $move_dots, $expand, $add_en_lang, $langcode, $sourcetitle, $revid);
    // ---
    return $text;
}
// $text = DoChangesToText1($sourcetitle, $text, $lang, $revid);

function DoChangesToText1($sourcetitle, $title, $text, $lang, $revid)
{
    // ---
    $text = fix_page_here($text, $title, $lang, $sourcetitle, $revid);
    // ---
    return $text;
}
