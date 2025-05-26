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
use function Publish\WD\LinkToWikidata;
use function Publish\FilesHelps\to_do;

function get_errors_file($editit, $place_holder)
{
    $to_do_file = $place_holder;
    // ---
    $errs_main = [
        "protectedpage",
        "titleblacklist",
        "ratelimited",
        "editconflict",
        "spam filter",
        "abusefilter",
        "mwoauth-invalid-authorization",
    ];
    // ---
    $errs_wd = [
        'Links to user pages' => "wd_user_pages",
        'get_csrftoken' => "wd_csrftoken",
        'protectedpage' => "wd_protectedpage",
    ];
    // ---
    $errs = ($place_holder == "errors") ? $errs_main : $errs_wd;
    // ---
    $c_text = json_encode($editit);
    // ---
    foreach ($errs as $err) {
        if (strpos($c_text, $err) !== false) {
            $to_do_file = $err;
            break;
        }
    }
    return $to_do_file;
}

function handleSuccessfulEdit($sourcetitle, $lang, $user, $title, $access_key, $access_secret)
{
    $LinkTowd = [];
    // ---
    try {
        $LinkTowd = LinkToWikidata($sourcetitle, $lang, $user, $title, $access_key, $access_secret) ?? [];
        // ---
        // Check if the error is get_csrftoken failure and user is not already "Mr. Ibrahem"  
        if (isset($LinkTowd['error']) &&   
            $LinkTowd['error'] == 'get_csrftoken failed' &&  
            $user !== 'Mr. Ibrahem') {  
              
            pub_test_print("get_csrftoken failed for user: $user, retrying with Mr. Ibrahem");  
              
            // Retry with "Mr. Ibrahem" credentials - get fresh credentials from database  
            $fallback_access = get_access_from_db_new('Mr. Ibrahem');  
            if ($fallback_access === null) {  
                $fallback_access = get_access_from_db('Mr. Ibrahem');  
            }  
              
            if ($fallback_access !== null) {  
                $fallback_access_key = $fallback_access['access_key'];  
                $fallback_access_secret = $fallback_access['access_secret'];  
                  
                $LinkTowd = LinkToWikidata($sourcetitle, $lang, 'Mr. Ibrahem', $title, $fallback_access_key, $fallback_access_secret) ?? [];  
                  
                // Add a note that fallback was used  
                if (!isset($LinkTowd['error'])) {  
                    $LinkTowd['fallback_user'] = 'Mr. Ibrahem';  
                    $LinkTowd['original_user'] = $user;  
                    pub_test_print("Successfully linked using Mr. Ibrahem fallback credentials");  
                }  
            }  
        }  
          
        // Log errors if they still exist after retry  
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
    // ---
    if (isset($LinkTowd['error'])) {
        $tab3 = [
            'error' => $LinkTowd['error'],
            'qid' => $LinkTowd['qid'] ?? "",
            'title' => $title,
            'sourcetitle' => $sourcetitle,
            'lang' => $lang,
            'username' => $user
        ];
        // if str($LinkTowd['error']) has "Links to user pages"  then file_name='wd_user_pages' else 'wd_errors'
        // ---
        $file_name = get_errors_file($LinkTowd['error'], "wd_errors");
        // ---
        to_do($tab3, $file_name);
    }
    // ---
    return $LinkTowd;
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
    $sourcetitle = $tab['sourcetitle'];
    $lang = $tab['lang'];
    $campaign = $tab['campaign'];
    $title = $tab['title'];
    $summary = $tab['summary'];

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
    } else {
        $to_do_file = get_errors_file($editit, "errors");
    }
    // ---
    $tab['result_to_cx'] = $editit;
    // ---
    to_do($tab, $to_do_file);
    // ---
    return $editit;
}
