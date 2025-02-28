<?php

namespace Publish\TextFix\DelDuplicateRefs;

/*

Usage:

use function Publish\TextFix\DelDuplicateRefs\remove_Duplicate_refs;

*/

use function Publish\TextFix\Citation\getCitations;

function remove_Duplicate_refs(string $text): string
{
    // ---
    $new_text = $text;
    // ---
    $refs_to_check = [];
    // ---
    $refs = [];
    // ---
    $citations = getCitations($text);
    // ---
    $numb = 0;
    // ---
    foreach ($citations as $key => $citation) {
        // ---
        $cite_text = $citation->getCiteText();
        // ---
        $cite_contents = $citation->getTemplate();
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
        // echo "\ncite_newtext: ($cite_newtext)\n";
        // ---
        if (isset($refs[$cite_attrs])) {
            // ---
            $new_text = str_replace($cite_text, $cite_newtext, $new_text);
        } else {
            $refs_to_check[$cite_newtext] = $cite_text;
            // ---
            $refs[$cite_attrs] = $cite_contents;
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
