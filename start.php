<?php
// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

use function Publish\Helps\pub_test_print;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\AccessHelpsNew\get_access_from_db_new;
use function Publish\WD\LinkToWikidata;
use function Publish\TextFix\DoChangesToText;
use function WpRefs\FixPage\DoChangesToText1;
use function Publish\EditProcess\processEdit;

// $rand_id = rand(0, 999999999);
$rand_id = time() .  "-" . bin2hex(random_bytes(6));

// $main_dir = check_dirs($rand_id, "reports");
$main_dir_by_day = check_dirs($rand_id, "reports_by_day");

function check_dirs($rand_id, $reports_dir_main)
{
    $publish_reports = "I:/mdwiki/publish-repo/publish_reports/";
    // ---
    if (!is_dir($publish_reports)) {
        $publish_reports = __DIR__ . "/../publish_reports/";
    }
    // ---
    $reports_dir = "$publish_reports/$reports_dir_main/";
    // ---
    if (!is_dir($reports_dir)) {
        mkdir($reports_dir, 0755, true);
    }
    // ---
    $year_dir = $reports_dir . date("Y");
    // ---
    if (!is_dir($year_dir)) {
        mkdir($year_dir, 0755, true);
    }
    // ---
    $month_dir = $year_dir . "/" . date("m");
    // ---
    if (!is_dir($month_dir)) {
        mkdir($month_dir, 0755, true);
    }
    // ---
    $day_dir = $month_dir . "/" . date("d");
    // ---
    if (!is_dir($day_dir)) {
        mkdir($day_dir, 0755, true);
    }
    // ---
    $main1_dir = $day_dir . "/" . $rand_id;
    // ---
    if (!is_dir($main1_dir)) {
        mkdir($main1_dir, 0755, true);
    }
    // ---
    return $main1_dir;
}

function get_revid($sourcetitle)
{
    // read all_pages_revids.json file
    $revids_file = __DIR__ . '/all_pages_revids.json';
    // ---
    if (!file_exists($revids_file)) $revids_file = __DIR__ . '/../all_pages_revids.json';
    // ---
    try {
        $json = json_decode(file_get_contents($revids_file), true);
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

function to_do($tab, $file_name)
{
    global $main_dir_by_day; // $main_dir,
    // ---
    $tab['time'] = time();
    $tab['time_date'] = date("Y-m-d H:i:s");
    // ---
    /*
    try {
        // dump $tab to file in folder to_do
        $file_j = $main_dir . "/$file_name.json";
        // ---
        file_put_contents($file_j, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {
        pub_test_print($e->getMessage());
    }*/
    // ---
    try {
        // dump $tab to file in folder to_do
        $file_j = $main_dir_by_day . "/$file_name.json";
        // ---
        file_put_contents($file_j, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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

function handleNoAccess($user, $tab)
{
    $error = ['code' => 'noaccess', 'info' => 'noaccess'];
    // ---
    $editit = ['error' => $error, 'edit' => ['error' => $error, 'username' => $user], 'username' => $user];
    // ---
    $tab['result_to_cx'] = $editit;
    // ---
    to_do($tab, "noaccess");
    // ---
    pub_test_print("\n<br>");
    pub_test_print("\n<br>");

    print(json_encode($editit, JSON_PRETTY_PRINT));

    // file_put_contents(__DIR__ . '/editit.json', json_encode($editit, JSON_PRETTY_PRINT));
}

function handleSuccessfulEdit($sourcetitle, $lang, $user, $title, $access_key, $access_secret)
{
    $LinkTowd = [];
    // ---
    try {
        $LinkTowd = LinkToWikidata($sourcetitle, $lang, $user, $title, $access_key, $access_secret) ?? [];
        // ---
    } catch (Exception $e) {
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
        $file_name = 'wd_errors';
        // ---
        $errs = [
            'Links to user pages' => "wd_user_pages",
            'get_csrftoken' => "wd_csrftoken",
            'protectedpage' => "wd_protectedpage",
        ];
        // ---
        $f_text = json_encode($LinkTowd['error']);
        // ---
        foreach ($errs as $err => $file) {
            if (strpos($f_text, $err) !== false) {
                $file_name = $file;
                break;
            }
        }
        // ---
        to_do($tab3, $file_name);
    }
    // ---
    return $LinkTowd;
}

function start2($request, $user, $access, $tab)
{
    // ---
    $text = $request['text'] ?? '';
    // ---
    // $summary = $request['summary'] ?? '';
    // $revid = $request['revid'] ?? '';
    // ---
    $revid = get_revid($tab['sourcetitle']);
    // ---
    $hashtag = determineHashtag($tab['title'], $user);
    // ---
    $tab['summary'] = make_summary($revid, $tab['sourcetitle'], $tab['lang'], $hashtag);
    // ---
    // file_put_contents(__DIR__ . '/post.log', print_r(getallheaders(), true));
    // ---
    $newtext = DoChangesToText1($tab['sourcetitle'], $tab['title'], $text, $tab['lang'], $revid);
    // ---
    if (!empty($newtext)) {
        $text = $newtext;
    }
    // ---
    $tabx = processEdit($request, $access, $text, $user, $tab);
    // ---
    pub_test_print("\n<br>");
    pub_test_print("\n<br>");
    // ---
    print(json_encode($tabx['editit'], JSON_PRETTY_PRINT));
    // ---
    // file_put_contents(__DIR__ . '/editit.json', json_encode($editit, JSON_PRETTY_PRINT));
    // ---
    to_do($tabx['tab'], $tabx['to_do_file']);
}


function start($request)
{
    // ---
    $user = formatUser($request['user'] ?? '');
    $title = formatTitle($request['title'] ?? '');
    // ---
    $tab = [
        'title' => $title,
        'summary' => "",
        'lang' => $request['target'] ?? '',
        'user' => $user,
        'campaign' => $request['campaign'] ?? '',
        'result' => "",
        'edit' => [],
        'sourcetitle' => $request['sourcetitle'] ?? ''
    ];
    // ---
    $access = get_access_from_db_new($user);
    // ---
    if ($access === null) {
        $access = get_access_from_db($user);
    }
    // ---
    if ($access == null) {
        handleNoAccess($user, $tab);
    } else {
        start2($request, $user, $access, $tab);
    }
}
start($_POST);
