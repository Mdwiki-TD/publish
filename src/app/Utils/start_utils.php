<?php
namespace Publish\StartUtils;

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
        "Admin"         => "Mr. Ibrahem",
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

function get_errors_file($editit, $placeHolder)
{
    $toDoFile = $placeHolder;
    $errsMain = [
        "protectedpage",
        "titleblacklist",
        "ratelimited",
        "editconflict",
        "spam filter",
        "abusefilter",
        "mwoauth-invalid-authorization",
        "mwoauth-invalid-authorization-invalid-user",
    ];
    $errsWd = [
        "Links to user pages" => "wd_user_pages",
        "getCsrfTokenData"    => "wd_csrftoken",
        "get_csrftoken"       => "wd_csrftoken",
        "protectedpage"       => "wd_protectedpage",
    ];
    $cText = json_encode($editit);
    if ($placeHolder == "errors") {
        foreach ($errsMain as $err) {
            if (strpos($cText, $err) !== false) {
                $toDoFile = $err;
                break;
            }
        }
    } else {
        foreach ($errsWd as $pattern => $result) {
            if (strpos($cText, $pattern) !== false) {
                $toDoFile = $result;
                break;
            }
        }
    }
    return $toDoFile;
}
