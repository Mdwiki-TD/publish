<?php

namespace WpRefs\Infobox;
/*
usage:

use function WpRefs\Infobox\Expend_Infobox;

*/

use function WpRefs\Infobox2\make_tempse;
use function WpRefs\Infobox2\expend_new;

function find_max_value_key($dictionary)
{
    // Sort the dictionary by value in descending order
    arsort($dictionary);

    // Return the first key
    return key($dictionary);
}

function make_main_temp($tempse_by_u, $tempse)
{
    // ---
    if (count($tempse_by_u) === 1) {
        return array_values($tempse_by_u)[0];
    }
    // ---
    $main_temp = [];
    // ---
    # sort tempse by len of its value then get the first one
    $u2 = find_max_value_key($tempse);
    # ---
    $main_temp = $tempse_by_u[$u2] ?? [];
    // ---
    return $main_temp;
}


function make_section_0($title, $newtext)
{
    /*
    make_section_0

    */
    // ---
    $section_0 = "";
    // ---
    if (strpos($newtext, "==") !== false) {
        $section_0 = explode("==", $newtext)[0];
    } else {
        $tagg = "'''" . $title . "'''1";
        if (strpos($newtext, $tagg) !== false) {
            $section_0 = explode($tagg, $newtext)[0];
        } else {
            $section_0 = $newtext;
            // print_s("section_0 = newtext");
        }
    }
    // ---
    return $section_0;
}


function fix_title_bold($text, $title)
{
    /*
    2020 2020
    */
    // ---
    try {
        $title2 = preg_quote($title, '/');
    } catch (\Exception $e) {
        $title2 = $title;
    }
    // ---
    $text = preg_replace("/\}\s*('''$title2''')/", "}\n\n$1", $text);
    // ---
    return $text;
}


function Expend_Infobox($text, $title, $section_0)
{
    // ---
    $newtext = $text;
    // ---
    if (!$section_0) {
        $section_0 = make_section_0($title, $newtext);
    }
    // ---
    $newtext = fix_title_bold($newtext, $title);
    $section_0 = fix_title_bold($section_0, $title);
    // ---
    $tab = make_tempse($section_0);
    // ---
    $tempse_by_u = $tab["tempse_by_u"];
    $tempse = $tab["tempse"];
    // ---
    $main_temp = make_main_temp($tempse_by_u, $tempse);
    // ---
    # work in main_temp:
    if (!empty($main_temp)) {
        $main_temp_text = $main_temp["item"] ?? "";
        // $params = $main_temp["params"] ?? [];
        // ---
        $new_temp = expend_new($main_temp_text);
        // ---
        if ($new_temp !== $main_temp_text) {
            $newtext = str_replace($main_temp_text, $new_temp, $newtext);
            $newtext = str_replace($new_temp . "'''", $new_temp . "\n'''", $newtext);
        }
    }
    // ---
    return $newtext;
}
