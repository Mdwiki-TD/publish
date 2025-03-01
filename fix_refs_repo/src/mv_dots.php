<?php

namespace WpRefs\MoveDots;

/*
usage:

use function WpRefs\MoveDots\move_dots_text;
use function WpRefs\MoveDots\add_lang_en;

*/

function move_dots_text($newtext, $lang)
{
    // ---
    // echo_test("move_dots_text\n");
    // ---
    $dot = "(\.|\,)";
    // ---
    if ($lang == "zh") {
        $dot = "(。)";
    }
    // ---
    $regline = "((?:\s*<ref[\s\S]+?(?:<\/ref|\/)>)+)";
    // ---
    $pattern = "/" . $dot . "\s*" . $regline . "/m";
    $replacement = "$2$1";
    // ---
    // echo_test("\n$pattern\n");
    // ---
    $newtext = preg_replace($pattern, $replacement, $newtext);
    // ---
    return $newtext;
}

function add_lang_en($text)
{
    // ---
    // Match references
    $REFS = "/(?is)(?P<pap><ref[^>\/]*>)(?P<ref>.*?<\/ref>)/";
    // ---
    if (preg_match_all($REFS, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $pap = $match['pap'];
            $ref = $match['ref'];
            // ---
            if (!trim($ref)) {
                continue;
            }
            // ---
            if (preg_replace("/\|\s*language\s*\=\s*\w+/", "", $ref) != $ref) {
                continue;
            }
            // ---
            $ref2 = preg_replace("/(\|\s*language\s*\=\s*)(\|\}\})/", "$1en$2", $ref);
            // ---
            if ($ref2 == $ref) {
                $ref2 = str_replace("}}</ref>", "|language=en}}</ref>", $ref);
            }
            // ---
            if ($ref2 != $ref) {
                $text = str_replace($pap . $ref, $pap . $ref2, $text);
            }
        }
    }
    // ---
    return $text;
}
