<?php

include_once __DIR__ . '/include.php';

use function Publish\Helps\pub_test_print;
use function Publish\DoEdit\publish_do_edit;
use function Publish\Helps\get_access_from_db;
use function Publish\AddToDb\InsertPageTarget;
use function Publish\AddToDb\retrieveCampaignCategories;
use function Publish\WD\LinkToWikidata;
use function Publish\TextFix\DoChangesToText;
use function WpRefs\FixPage\DoChangesToText1;

function get_revid($sourcetitle)
{
    // read all_pages_revids.json file
    try {
        $json = json_decode(file_get_contents(__DIR__ . '/all_pages_revids.json'), true);
        $revid = $json[$sourcetitle] ?? "";
        return $revid;
    } catch (Exception $e) {
        pub_test_print($e->getMessage());
    }
    try {
        $json = json_decode(file_get_contents(__DIR__ . '/../all_pages_revids.json'), true);
        $revid = $json[$sourcetitle] ?? "";
        return $revid;
    } catch (Exception $e) {
        pub_test_print($e->getMessage());
    }
    return "";
}

function make_summary($revid, $sourcetitle, $to, $hashtag)
{
    return "Created by translating the page [[:mdwiki:Special:Redirect/revision/$revid|$sourcetitle]] to:$to $hashtag";
}

function to_do($tab, $dir)
{
    $main_dir = __DIR__ . "/../publish_reports/" . $dir;
    if (!is_dir($main_dir)) {
        mkdir($main_dir, 0755, true);
    }
    try {
        // dump $tab to file in folder to_do
        $file_name = $main_dir . "/$dir" . "_" . rand(0, 999999999) . ".json";
        file_put_contents($file_name, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {
        pub_test_print($e->getMessage());
    }
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

function handleNoAccess($user, $tab)
{
    $error = ['code' => 'noaccess', 'info' => 'noaccess'];
    $editit = ['error' => $error, 'edit' => ['error' => $error, 'username' => $user], 'username' => $user];
    $to_do_dir = "errors";
    // ---
    $tab['edit'] = $editit;
    to_do($tab, $to_do_dir);

    pub_test_print("\n<br>");
    pub_test_print("\n<br>");

    print(json_encode($editit, JSON_PRETTY_PRINT));

    // file_put_contents(__DIR__ . '/editit.json', json_encode($editit, JSON_PRETTY_PRINT));
}

function processEdit($access, $sourcetitle, $text, $lang, $revid, $campaign, $user, $title, $summary, $request, $tab)
{
    $apiParams = prepareApiParams($title, $summary, $text, $request);

    $access_key = $access['access_key'];
    $access_secret = $access['access_secret'];

    // $text = fix_wikirefs($text, $lang);
    $newtext = DoChangesToText1($sourcetitle, $title, $text, $lang, $revid);
    /*
    if ($user == "Mr. Ibrahem") {
        $newtext = DoChangesToText1($sourcetitle, $title, $text, $lang, $revid);
    } else {
        $newtext = DoChangesToText($sourcetitle, $title, $text, $lang, $revid);
    }
    */

    if (!empty($newtext)) {
        $text = $newtext;
    }

    $apiParams["text"] = $text;

    $editit = publish_do_edit($apiParams, $lang, $access_key, $access_secret);

    $Success = $editit['edit']['result'] ?? '';

    $tab['result'] = $Success;
    $to_do_dir = "";

    if ($Success === 'Success') {
        $editit['LinkToWikidata'] = handleSuccessfulEdit($sourcetitle, $campaign, $lang, $user, $title, $editit, $access_key, $access_secret);
        $to_do_dir = "success";
    } else {
        $to_do_dir = "errors";
    }

    $tab['edit'] = $editit;
    to_do($tab, $to_do_dir);

    pub_test_print("\n<br>");
    pub_test_print("\n<br>");

    print(json_encode($editit, JSON_PRETTY_PRINT));

    // file_put_contents(__DIR__ . '/editit.json', json_encode($editit, JSON_PRETTY_PRINT));
}

function handleSuccessfulEdit($sourcetitle, $campaign, $lang, $user, $title, $editit, $access_key, $access_secret)
{
    $camp_to_cat = retrieveCampaignCategories();
    $cat = $camp_to_cat[$campaign] ?? '';
    $LinkToWikidata = [];

    try {
        $is_user_page = InsertPageTarget($sourcetitle, 'lead', $cat, $lang, $user, "", $title);
        $LinkToWikidata = LinkToWikidata($sourcetitle, $lang, $user, $title, $access_key, $access_secret);

        if (isset($LinkToWikidata['error']) && !isset($LinkToWikidata['nserror'])) {
            $tab3 = [
                'error' => $LinkToWikidata['error'],
                'qid' => $LinkToWikidata['qid'] ?? "",
                'title' => $title,
                'sourcetitle' => $sourcetitle,
                'lang' => $lang,
                'username' => $user
            ];
            to_do($tab3, 'wd_errors');
        }
    } catch (Exception $e) {
        pub_test_print($e->getMessage());
    }
    return $LinkToWikidata;
}

function start($request)
{
    $sourcetitle = $request['sourcetitle'] ?? '';
    $title = formatTitle($request['title'] ?? '');
    $user = formatUser($request['user'] ?? '');
    $lang = $request['target'] ?? '';
    $text = $request['text'] ?? '';
    $campaign = $request['campaign'] ?? '';
    $summary = $request['summary'] ?? '';

    // $revid = $request['revid'] ?? '';
    $revid = get_revid($sourcetitle);
    $hashtag = determineHashtag($title, $user);
    $summary = make_summary($revid, $sourcetitle, $lang, $hashtag);

    $access = get_access_from_db($user);

    $tab = [
        'title' => $title,
        'summary' => $summary,
        'lang' => $lang,
        'user' => $user,
        'campaign' => $campaign,
        'result' => "",
        'edit' => [],
        'sourcetitle' => $sourcetitle
    ];

    if ($access == null) {
        handleNoAccess($user, $tab);
    } else {
        processEdit($access, $sourcetitle, $text, $lang, $revid, $campaign, $user, $title, $summary, $request, $tab);
    }
}


start($_REQUEST);
