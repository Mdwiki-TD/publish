<?php

namespace Publish\EditProcess;
/*
Usage:
use function Publish\EditProcess\processEdit;
*/

use function Publish\Helps\pub_test_print;
use function Publish\AddToDb\InsertPublishReports;
use function Publish\WD\LinkToWikidata;
use function Publish\FilesHelps\to_do;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\WikiApi\GetTitleInfo;
use function Publish\EditProcess\add_to_db;
use function Publish\DoEdit\publish_do_edit;
use function Publish\StartUtils\get_errors_file;
use function Publish\StartUtils\prepareApiParams;


function shouldAddedToWikidata($lang, $title)
{
    $pageInformations = GetTitleInfo($title, $lang);
    if (!$pageInformations) {
        return false;
    }
    $pageNamespace = $pageInformations["ns"] ?? null;
    if ($pageNamespace == 2) {
        // skip link to wd for user pages
        return false;
    }
    return true;
}

function retryWithFallbackUser($sourcetitle, $lang, $title, $user)
{
    $LinkTowd = [];
    pub_test_print("get_csrftoken failed for user: $user, retrying with Mr. Ibrahem");

    // Retry with "Mr. Ibrahem" credentials - get fresh credentials from database
    $fallbackAccess = get_access_from_db('Mr. Ibrahem');

    if (!empty($fallbackAccess)) {
        $fallbackAccessKey = $fallbackAccess['access_key'];
        $fallbackAccessSecret = $fallbackAccess['access_secret'];

        $LinkTowd = LinkToWikidata($sourcetitle, $lang, 'Mr. Ibrahem', $title, $fallbackAccessKey, $fallbackAccessSecret) ?? [];

        // Add a note that fallback was used
        if (!isset($LinkTowd['error'])) {
            $LinkTowd['fallback_user'] = 'Mr. Ibrahem';
            $LinkTowd['original_user'] = $user;
            pub_test_print("Successfully linked using Mr. Ibrahem fallback credentials");
        }
    }
    return $LinkTowd;
}

function handleSuccessfulEdit($sourcetitle, $lang, $user, $title, $access, $randId)
{
    if (!shouldAddedToWikidata($lang, $title)) {
        // skip link to wd for user pages
        return ["error" => "skip link to wd for user pages"];
    }
    $LinkTowd = [];
    $accessKey = $access['access_key'];
    $accessSecret = $access['access_secret'];

    try {
        $LinkTowd = LinkToWikidata($sourcetitle, $lang, $user, $title, $accessKey, $accessSecret) ?? [];
        // Check if the error is get_csrftoken failure and user is not already "Mr. Ibrahem"
        if (isset($LinkTowd['error']) && $LinkTowd['error'] == 'get_csrftoken failed' && $user !== 'Mr. Ibrahem') {
            $LinkTowd['fallback'] = retryWithFallbackUser($sourcetitle, $lang, $title, $user);
        }
        // Log errors if they still exist after retry
    } catch (\Exception $e) {
        pub_test_print($e->getMessage());
    }
    if (isset($LinkTowd['error'])) {
        $tab3 = [
            'error' => $LinkTowd['error'],
            'qid' => $LinkTowd['qid'] ?? "",
            'title' => $title,
            'sourcetitle' => $sourcetitle,
            'fallback' => $LinkTowd['fallback'] ?? "",
            'lang' => $lang,
            'username' => $user
        ];
        // if str($LinkTowd['error']) has "Links to user pages"  then file_name='wd_user_pages' else 'wd_errors'
        $fileName = get_errors_file($LinkTowd['error'], "wd_errors");
        to_do($tab3, $fileName, $randId);
        // --
        InsertPublishReports($title, $user, $lang, $sourcetitle, $fileName, $tab3);
    }
    return $LinkTowd;
}

function processEdit($request, $access, $text, $user, $tab, $randId, $trType)
{
    $sourcetitle = $tab['sourcetitle'];
    $lang = $tab['lang'];
    $campaign = $tab['campaign'];
    $title = $tab['title'];
    $summary = $tab['summary'];
    $mdwikiRevid = $tab['revid'] ?? "";

    $apiParams = prepareApiParams($title, $summary, $text, $request);

    $apiParams["text"] = $text;

    $editit = publish_do_edit($apiParams, $lang, $access);

    $Success = $editit['edit']['result'] ?? '';
    $isCaptcha = $editit['edit']['captcha'] ?? null;

    $tab['result'] = $Success;

    $toDoFile = "";

    $words = $tab["words"];

    if ($Success === 'Success') {
        $linktowikidata = handleSuccessfulEdit($sourcetitle, $lang, $user, $title, $access, $randId);
        $editit['LinkToWikidata'] = $linktowikidata;

        $toUsersTable = false;
        // if $wdResult has "abusefilter-warning-39" then $toUsersTable = true
        if (strpos(json_encode($linktowikidata), "abusefilter-warning-39") !== false) {
            $toUsersTable = true;
        }
        $editit['sql_result'] = add_to_db($title, $lang, $user, $toUsersTable, $campaign, $sourcetitle, $mdwikiRevid, $words, $trType);
        $toDoFile = "success";
    } else if ($isCaptcha) {
        $toDoFile = "captcha";
    } else {
        $toDoFile = get_errors_file($editit, "errors");
    }
    $tab['result_to_cx'] = $editit;
    to_do($tab, $toDoFile, $randId);
    // --
    InsertPublishReports($title, $user, $lang, $sourcetitle, $toDoFile, $tab);
    return $editit;
}
