<?php

namespace WikiParse\Citations;

include_once __DIR__ . '/include_it.php';

/*
Usage:

use function WikiParse\Citations\getCitations;

*/

use WikiConnect\ParseWiki\ParserCitations;

function getCitations($text)
{
    $do = new ParserCitations($text);
    $citations = $do->getCitations();

    return $citations;
}
