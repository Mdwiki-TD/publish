<?php

namespace Publish\TextFix\DelDuplicateRefs;

/*

Usage:

use function Publish\TextFix\DelDuplicateRefs\remove_Duplicate_refs;

*/

use function Publish\TextFix\Citation\getCitations;


function get_html_attribute_value(string $text, string $param): string
{
    if (empty($text)) {
        return '';
    }

    // Case-insensitive regular expression for attribute extraction
    $pattern = sprintf('/(?i)%s\s*=\s*[\'"]?(?P<%s>[^\'" >]+)[\'"]?/', $param, $param);
    $match = preg_match($pattern, $text, $matches);

    if ($match) {
        return $matches[$param];
    }

    return '';
}
function remove_Duplicate_refs(string $text): string
{
    // ---
    $new_text = $text;
    // ---
    $refs = [];
    // ---
    $citations = getCitations($text);
    // ---
    $new_text = $text;
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
        // echo "\n$cite_newtext";
        // ---
        if (isset($refs[$cite_attrs])) {
            // ---
            $new_text = str_replace($cite_text, $cite_newtext, $new_text);
        } else {
            $refs[$cite_attrs] = $cite_newtext;
        };
    }
    // ---
    return $new_text;
}
