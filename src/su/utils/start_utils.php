<?php

namespace Publish\StartUtils;

/*
Usage:
use function Publish\StartUtils\make_summary;
use function Publish\StartUtils\formatTitle;
use function Publish\StartUtils\formatUser;
use function Publish\StartUtils\determineHashtag;
use function Publish\StartUtils\get_errors_file;
use function Publish\StartUtils\prepareApiParams;
*/

function make_summary($revid, $sourcetitle, $to, $hashtag)
{
    return "Created by translating the page [[:mdwiki:Special:Redirect/revision/$revid|$sourcetitle]] to:$to $hashtag";
}

function formatTitle($title)
{
    $title = str_replace("_", " ", $title);
    // replace Mr. Ibrahem 1/ by Mr. Ibrahem/
    $title = str_replace("Mr. Ibrahem 1/", "Mr. Ibrahem/", $title);
    return $title;
}

function formatUser($user)
{
    $specialUsers = [
        "Mr. Ibrahem 1" => "Mr. Ibrahem",
        "Admin" => "Mr. Ibrahem"
    ];
    $user = $specialUsers[$user] ?? $user;
    return str_replace("_", " ", $user);
}

function determineHashtag($title, $user)
{
    $hashtag = "#mdwikicx";

    if (strpos($title, "Mr. Ibrahem") !== false && $user == "Mr. Ibrahem") {
        $hashtag = "";
    }
    return $hashtag;
}


function get_errors_file($editit, $place_holder)
{
    $to_do_file = $place_holder;
    $errs_main = [
        "protectedpage",
        "titleblacklist",
        "ratelimited",
        "editconflict",
        "spam filter",
        "abusefilter",
        "mwoauth-invalid-authorization",
    ];
    $errs_wd = [
        "Links to user pages" => "wd_user_pages",
        "get_csrftoken" => "wd_csrftoken",
        "protectedpage" => "wd_protectedpage",
    ];
    $c_text = json_encode($editit);
    if ($place_holder == "errors") {
        foreach ($errs_main as $err) {
            if (strpos($c_text, $err) !== false) {
                $to_do_file = $err;
                break;
            }
        }
    } else {
        foreach ($errs_wd as $pattern => $result) {
            if (strpos($c_text, $pattern) !== false) {
                $to_do_file = $result;
                break;
            }
        }
    }
    return $to_do_file;
}

function prepareApiParams($title, $summary, $text, $request)
{
    $apiParams = [
        'action' => 'edit',
        'title' => $title,
        // 'section' => 'new',
        'summary' => $summary,
        'text' => $text,
        'format' => 'json',
    ];

    // wpCaptchaId, wpCaptchaWord
    if (isset($request['wpCaptchaId']) && isset($request['wpCaptchaWord'])) {
        $apiParams['wpCaptchaId'] = $request['wpCaptchaId'];
        $apiParams['wpCaptchaWord'] = $request['wpCaptchaWord'];
    }
    return $apiParams;
}
