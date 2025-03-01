<?php

namespace WikiParse\Template;

include_once __DIR__ . '/include_it.php';

/*
Usage:

use function WikiParse\Template\getTemplate;
use function WikiParse\Template\getTemplates;

*/

// include_once __DIR__ . '/../WikiParse/Template.php';

use WikiConnect\ParseWiki\ParserTemplate;
use WikiConnect\ParseWiki\ParserTemplates;

function getTemplate($text)
{
    $parser = new ParserTemplate($text);
    $temp = $parser->getTemplate();
    return $temp;
}

function getTemplates($text)
{
    if (empty($text)) {
        return [];
    }
    $parser = new ParserTemplates($text);
    $temps = $parser->getTemplates();
    return $temps;
}
