<?php

namespace Publish\EditProcess;

use function Publish\MdwikiSql\fetch_query;

function get_lang_settings($langCode)
{
    $query = "SELECT move_dots, expend, add_en_lang FROM language_settings where lang_code = ?";
    $result = fetch_query($query, [$langCode]);

    if (!$result) {
        return null;
    }
    $result = $result[0];
    return $result;
}

function text_changes($sourcetitle, $title, $text, $lang, $mdwikiRevid)
{
    if (function_exists('\WpRefs\FixPage\fix_page_with_setting')) {
        $settings = get_lang_settings($lang) ?: [];

        $moveDots = $settings['move_dots'] ?? null;
        $expand = $settings['expend'] ?? null;
        $addEnLang = $settings['add_en_lang'] ?? null;

        $newtext = \WpRefs\FixPage\fix_page_with_setting(
            $sourcetitle,
            $title,
            $text,
            $lang,
            $mdwikiRevid,
            $moveDots,
            $expand,
            $addEnLang,
        );
        return $newtext;
    }
    if (function_exists('\WpRefs\FixPage\DoChangesToText1')) {
        $text = \WpRefs\FixPage\DoChangesToText1($sourcetitle, $title, $text, $lang, $mdwikiRevid);
    }
    return $text;
}
