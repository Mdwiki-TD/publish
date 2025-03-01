<?php

namespace WpRefs\ExpendRefs;

/*
Usage:

use function WpRefs\ExpendRefs\refs_expend_work;

*/

use function WikiParse\Reg_Citations\get_full_refs;
use function WikiParse\Reg_Citations\getShortCitations;

function refs_expend_work($first, $alltext = "")
{
    if (empty($alltext)) {
        $alltext = $first;
    }
    $refs = get_full_refs($alltext);
    // echo  "get_full_refs:" . count($refs) . "<br>";

    $short_refs = getShortCitations($first);
    // echo  "short_refs:" . count($short_refs) . "<br>";

    foreach ($short_refs as $cite) {
        $name = $cite["name"];
        $refe = $cite["tag"];
        // ---
        $rr = $refs[$name] ?? false;
        if ($rr) {
            $first = str_replace($refe, $rr, $first);
        }
    }
    return $first;
}
