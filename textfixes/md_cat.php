<?php

namespace Publish\MdCat;

/*

use function Publish\MdCat\Add_MdWiki_Category;

*/

function get_cats()
{
    $url = "https://www.wikidata.org/w/rest.php/wikibase/v1/entities/items/Q107014860/sitelinks";
    // ---
    $json = file_get_contents($url);
    // ---
    $json = json_decode($json, true);
    // ---
    return $json;
}
function Add_MdWiki_Category($lang)
{
    // ---
    $cats = get_cats();
    // ---
    $cat = $cats["$lang" . "wiki"] ?? "Category:Translated from MDWiki";
    // ---
    $cat = "[[$cat]]";
    // ---
    return $cat;
}
