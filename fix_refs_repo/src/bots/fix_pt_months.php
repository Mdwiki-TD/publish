<?php

namespace WpRefs\FixPtMonth;
/*
usage:

use function WpRefs\FixPtMonth\pt_months;

*/

// use function WikiParse\Citations\get_full_refs;
use function WikiParse\Citations\getCitations;
use function WikiParse\Template\getTemplate;

// Define the Spanish month translations
$pt_months_tab = [
    "January" => "janeiro",
    "February" => "fevereiro",
    "March" => "março",
    "April" => "abril",
    "May" => "maio",
    "June" => "junho",
    "July" => "julho",
    "August" => "agosto",
    "September" => "setembro",
    "October" => "outubro",
    "November" => "novembro",
    "December" => "dezembro",
];

$pt_months_lower = array_change_key_case($pt_months_tab, CASE_LOWER);

function make_new_pt_val($val)
{
    global $pt_months_lower;
    // ---
    $newVal = $val;
    // ---
    $patterns = [
        // Match date like: January, 2020 or 10 January, 2020
        '/^(?P<d>\d{1,2} |)(?P<m>January|February|March|April|May|June|July|August|September|October|November|December),* (?P<y>\d{4})$/',
        // Match date like: January 10, 2020
        '/^(?P<m>January|February|March|April|May|June|July|August|September|October|November|December) (?P<d>\d{1,2}),* (?P<y>\d{4})$/',
    ];
    // ---
    foreach ($patterns as $pattern) {
        preg_match($pattern, trim($val), $matches);
        // ---
        if ($matches) {
            $day = trim($matches['d']);
            $month = trim($matches['m']);
            $year = trim($matches['y']);
            // ---
            // echo_test("day:$val\n");
            // echo_test("day:$day, month:$month, year:$year\n");
            // ---
            $translatedMonth = $pt_months_lower[strtolower($month)] ?? "";

            if (!empty($translatedMonth)) {
                if (!empty($day)) {
                    $translatedMonth = "de $translatedMonth";
                }

                $newVal = "$day $translatedMonth $year";
                return trim($newVal);
            }
        }
    }

    return trim($newVal);
}


function fix_one_cite_text($temp_text)
{
    // ---
    $temp_text = trim($temp_text);
    // ---
    $temp = getTemplate($temp_text);
    // ---
    $params = $temp->getParameters();
    // ---
    foreach ($params as $key => $value) {
        // ---
        $new_value = make_new_pt_val($value);
        // ---
        if ($new_value && $new_value != $value) {
            $temp->setParameter($key, $new_value);
        }
    }
    // ---
    $new_text = $temp->toString();
    // ---
    return $new_text;
}

function pt_months($text)
{
    // ---
    $citations = getCitations($text);
    // ---
    $new_text = $text;
    // ---
    foreach ($citations as $key => $citation) {
        // ---
        $cite_temp = $citation->getTemplate();
        // ---
        // if $cite_temp startwith {{ and ends with }}
        if (strpos($cite_temp, "{{") === 0 && strpos($cite_temp, "}}") === strlen($cite_temp) - 2) {
            // ---
            // echo_test("\n$cite_temp\n");
            // ---
            $new_temp = fix_one_cite_text($cite_temp);
            // ---
            $new_text = str_replace($cite_temp, $new_temp, $new_text);
        }
    }
    // ---
    return $new_text;
}
