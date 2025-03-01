<?php

namespace WpRefs\SW;
/*

usage:

use function WpRefs\SW\sw_fixes;

*/

function sw_fixes($text)
{
    // ---
    // find == Marejeleo == replace by == Marejeo ==
    $text = preg_replace('/==\s*Marejeleo\s*==/i', '== Marejeo ==', $text);
    // ---
    return $text;
}
