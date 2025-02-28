<?php

include_once __DIR__ . '/include.php';

use function Publish\Helps\pub_test_print;
use function Publish\DoEdit\publish_do_edit;
use function Publish\Helps\get_access_from_db;
use function Publish\AddToDb\InsertPageTarget;
use function Publish\AddToDb\retrieveCampaignCategories;
use function Publish\WD\LinkToWikidata;
use function Publish\TextFix\DoChangesToText;

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
    return "";
}

function make_summary($revid, $sourcetitle, $to, $hashtag)
{
    return "Created by translating the page [[:mdwiki:Special:Redirect/revision/$revid|$sourcetitle]] to:$to $hashtag";
}

function to_do($tab, $dir)
{
    if (!is_dir(__DIR__ . "/$dir")) {
        mkdir(__DIR__ . "/$dir", 0755, true);
    }
    try {
        // dump $tab to file in folder to_do
        $file_name = __DIR__ . "/$dir/" . rand(0, 999999999) . '.json';
        file_put_contents($file_name, json_encode($tab, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
        pub_test_print($e->getMessage());
    }
}

function formatTitle($title)
{
    return str_replace("_", " ", $title);
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
    $newtext = DoChangesToText($sourcetitle, $title, $text, $lang, $revid);

    if (!empty($text)) {
        $text = $newtext;
    }

    $apiParams["text"] = $text;

    $editit = publish_do_edit($apiParams, $lang, $access_key, $access_secret);

    $Success = $editit['edit']['result'] ?? '';

    $tab['result'] = $Success;

    if ($Success === 'Success') {
        $editit['LinkToWikidata'] = handleSuccessfulEdit($sourcetitle, $campaign, $lang, $user, $title, $editit, $access_key, $access_secret);
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
