<?php

namespace Publish\EditProcess;
/*
Usage:
use function Publish\EditProcess\processEdit;
*/

use function Publish\Helps\pub_test_print;
use function Publish\DoEdit\publish_do_edit;
use function Publish\AddToDb\InsertPageTarget;
use function Publish\AddToDb\retrieveCampaignCategories;

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

function add_to_db($title, $lang, $user, $wd_result, $campaign, $sourcetitle)
{
    // ---
    $camp_to_cat = retrieveCampaignCategories();
    $cat = $camp_to_cat[$campaign] ?? '';
    $to_users_table = false;
    // ---
    // if $wd_result has "abusefilter-warning-39" then $to_users_table = true
    if (strpos(json_encode($wd_result), "abusefilter-warning-39") !== false) {
        $to_users_table = true;
    }
    // ---
    $is_user_page = InsertPageTarget($sourcetitle, 'lead', $cat, $lang, $user, "", $title, $to_users_table);
    // ---
    return $is_user_page;
}

function processEdit($request, $access, $text, $user, $tab)
{
    // ---
    $sourcetitle = $tab['sourcetitle'];
    $lang = $tab['lang'];
    $campaign = $tab['campaign'];
    $title = $tab['title'];
    $summary = $tab['summary'];
    // ---
    $apiParams = prepareApiParams($title, $summary, $text, $request);

    $access_key = $access['access_key'];
    $access_secret = $access['access_secret'];

    $apiParams["text"] = $text;

    $editit = publish_do_edit($apiParams, $lang, $access_key, $access_secret);

    $Success = $editit['edit']['result'] ?? '';
    $is_captcha = $editit['edit']['captcha'] ?? null;

    $tab['result'] = $Success;

    $to_do_file = "";

    if ($Success === 'Success') {
        $editit['LinkToWikidata'] = handleSuccessfulEdit($sourcetitle, $lang, $user, $title, $access_key, $access_secret);
        // ---
        $editit['sql_result'] = add_to_db($title, $lang, $user, $editit['LinkToWikidata'], $campaign, $sourcetitle);
        // ---
        $to_do_file = "success";
        // ---
    } else if ($is_captcha) {
        $to_do_file = "captcha";
        // ---
    } else {
        $to_do_file = "errors";
        // ---
        $errs = [
            "protectedpage",
            "titleblacklist",
            "ratelimited",
            "editconflict",
            "spam filter",
            "abusefilter",
            "mwoauth-invalid-authorization",
        ];
        // ---
        $c_text = json_encode($editit);
        // ---
        foreach ($errs as $err) {
            if (strpos($c_text, $err) !== false) {
                $to_do_file = $err;
                break;
            }
        }
    }
    // ---
    $tab['result_to_cx'] = $editit;
    // ---
    return [
        "editit" => $editit,
        "tab" => $tab,
        "to_do_file" => $to_do_file
    ];
}
