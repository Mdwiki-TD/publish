<?php

namespace WpRefs\DelDuplicateRefs;

/*

Usage:

use function WpRefs\DelDuplicateRefs\remove_Duplicate_refs;

*/

use function WikiParse\Citations\getCitations;

function get_attrs($text)
{
    $text = "<ref $text>";
    $attrfind_tolerant = '/((?<=[\'"\s\/])[^\s\/>][^\s\/=>]*)(\s*=+\s*(\'[^\']*\'|"[^"]*"|(?![\'"])[^>\s]*))?(?:\s|\/(?!>))*/';
    $attrs = [];

    if (preg_match_all($attrfind_tolerant, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $attr_name = strtolower($match[1]);
            $attr_value = isset($match[3]) ? $match[3] : "";
            $attrs[$attr_name] = $attr_value;
        }
    }
    // ---
    var_export($attrs);
    // ---
    return $attrs;
}

function remove_Duplicate_refs(string $text): string
{
    // ---
    $new_text = $text;
    // ---
    $refs_to_check = [];
    // ---
    $refs = [];
    // ---
    $citations = getCitations($new_text);
    // ---
    $numb = 0;
    // ---
    foreach ($citations as $key => $citation) {
        // ---
        $cite_text = $citation->getCiteText();
        // ---
        // $cite_contents = $citation->getTemplate();
        // ---
        $cite_attrs = $citation->getOptions();
        $cite_attrs = $cite_attrs ? trim($cite_attrs) : "";
        // ---
        if (empty($cite_attrs)) {
            $numb += 1;
            $name = "autogen_" . $numb;
            $cite_attrs = "name='$name'";
        }
        // ---
        $cite_newtext = "<ref $cite_attrs />";
        // ---
        // echo_test("\n$cite_newtext\n");
        // ---
        if (isset($refs[$cite_attrs])) {
            // ---
            $new_text = str_replace($cite_text, $cite_newtext, $new_text);
        } else {
            $refs_to_check[$cite_newtext] = $cite_text;
            // ---
            $refs[$cite_attrs] = $cite_newtext;
        };
    }
    // ---
    foreach ($refs_to_check as $key => $value) {
        if (strpos($new_text, $value) === false) {
            $pattern = '/' . preg_quote($key, '/') . '/';
            $new_text = preg_replace($pattern, $value, $new_text, 1);
        }
    }
    // ---
    // echo count($citations);
    // ---
    return $new_text;
}

function del_start_end(string $text, string $find): string
{
    // ---
    $text = trim($text);
    // ---
    if (str_starts_with($text, $find) && str_ends_with($text, $find)) {
        $text = substr($text, strlen($find)); // إزالة $find من البداية
        $text = substr($text, 0, -strlen($find)); // إزالة $find من النهاية
    }
    // ---
    return trim($text);
}


function fix_attr_value(string $text): string
{
    // ---
    $text = trim($text);
    // ---
    $text = del_start_end($text, '"');
    // ---
    $text = del_start_end($text, "'");
    // ---
    // echo_test("\n$text\n");
    // ---
    $text = (strpos($text, '"') === false) ? '"' . $text . '"' : "'" . $text . "'";
    // ---
    return trim($text);
}

function fix_refs_names(string $text): string
{
    // ---
    $new_text = $text;
    // ---
    $citations = getCitations($text);
    // ---
    $new_text = $text;
    // ---
    foreach ($citations as $key => $citation) {
        // ---
        $cite_attrs = $citation->getOptions();
        $cite_attrs = $cite_attrs ? trim($cite_attrs) : "";
        // ---
        $if_in = "<ref $cite_attrs>";
        // ---
        if (strpos($new_text, $if_in) === false) {
            continue;
        }
        // ---
        $attrs = get_attrs($cite_attrs);
        // ---
        if (empty($cite_attrs)) {
            continue;
        }
        // ---
        $new_cite_attrs = "";
        // ---
        foreach ($attrs as $key => $value) {
            // ---
            $value2 = fix_attr_value($value);
            // ---
            $new_cite_attrs .= " $key=$value2";
            // ---
        }
        // ---
        $new_cite_attrs = trim($new_cite_attrs);
        // ---
        $cite_newtext = "<ref $new_cite_attrs>";
        // ---
        $new_text = str_replace($if_in, $cite_newtext, $new_text);
    }
    // ---
    return $new_text;
}
