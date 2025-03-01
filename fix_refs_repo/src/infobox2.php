<?php

namespace WpRefs\Infobox2;
/*
usage:

use function WpRefs\Infobox2\make_tempse;
use function WpRefs\Infobox2\expend_new;

*/

use function WpRefs\Bots\TxtLib2\extract_templates_and_params;
use WikiConnect\ParseWiki\ParserTemplate;

function do_comments($text)
{
    $pattern = '/\s*\n*\s*(<!-- (Monoclonal antibody data|External links|Names*|Clinical data|Legal data|Legal status|Pharmacokinetic data|Chemical and physical data|Definition and medical uses|Chemical data|\w+ \w+ data|\w+ \w+ \w+ data|\w+ data|\w+ status|Identifiers) -->)\s*\n*/s';
    preg_match_all($pattern, $text, $matches);

    foreach ($matches[0] as $match) {
        $match2 = trim($match);
        $text = str_replace($match, "\n\n$match2\n", $text);
    }

    return $text;
}
function expend_new($main_temp)
{
    // ---
    $main_temp = trim($main_temp);
    // ---
    $parser = new ParserTemplate($main_temp);
    // ---
    $temp = $parser->getTemplate();
    // ---
    $new_temp = $temp->toString($newLine = true, $ljust = 17);
    // ---
    $new_temp = do_comments($new_temp);
    // ---
    $new_temp = trim($new_temp);
    // ---
    return $new_temp;
}

function make_tempse($section_0)
{
    $tempse_by_u = [];
    $tempse = [];

    $ingr = extract_templates_and_params($section_0);
    $u = 0;

    foreach ($ingr as $temp) {
        $u++;
        $tmp_name = $temp['name'];
        $params = $temp['params'];
        $template = $temp['item'];

        if (count($params) > 4 && strpos($section_0, ">$template") === false) {
            $tempse_by_u[$u] = $temp;
            $tempse[$u] = strlen($template);
            // ---
            // print_s($namestrip);
        }
    }
    // ---
    return [
        "tempse_by_u" => $tempse_by_u,
        "tempse" => $tempse,
    ];
}
