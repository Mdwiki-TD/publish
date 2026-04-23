<?php

namespace Publish\EditProcess;

use function Publish\MdwikiSql\fetch_query;

function get_lang_settings($lang_code)
{
    $query = "SELECT move_dots, expend, add_en_lang FROM language_settings where lang_code = ?";
    $result = fetch_query($query, [$lang_code]);

    if (!$result) {
        return null;
    }
    $result = $result[0];
    return $result;
}

function text_changes($sourcetitle, $title, $text, $lang, $mdwiki_revid)
{
    if (function_exists('\WpRefs\FixPage\fix_page_with_setting')) {
        $settings = get_lang_settings($lang);

        $move_dots = $settings['move_dots'] ?: null;
        $expand = $settings['expend'] ?: null;
        $add_en_lang = $settings['add_en_lang'] ?: null;

        $newtext = \WpRefs\FixPage\fix_page_with_setting(
            $sourcetitle,
            $title,
            $text,
            $lang,
            $mdwiki_revid,
            // $move_dots = $move_dots,
            // $expand = $expand,
            // $add_en_lang = $add_en_lang,
            $move_dots,
            $expand,
            $add_en_lang,
        );
        return $newtext;
    }
    if (function_exists('\WpRefs\FixPage\DoChangesToText1')) {
        $text = \WpRefs\FixPage\DoChangesToText1($sourcetitle, $title, $text, $lang, $mdwiki_revid);
    }
    return $text;
}
