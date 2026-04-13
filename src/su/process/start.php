<?php

namespace Publish\Start;

use function Publish\EditProcess\text_changes;
use function Publish\Helps\pub_test_print;
use function Publish\AccessHelps\get_user_access;
use function Publish\EditProcess\processEdit;
use function Publish\FilesHelps\to_do;
use function Publish\Revids\get_revid_db;
use function Publish\Revids\get_revid;
use function Publish\AddToDb\InsertPublishReports;
use function Publish\StartUtils\make_summary;
use function Publish\StartUtils\formatTitle;
use function Publish\StartUtils\formatUser;
use function Publish\StartUtils\determineHashtag;

function load_words_table()
{

    $word_file = __DIR__ . "/../../td/Tables/jsons/words.json";
    if (!file_exists($word_file)) {
        $word_file = "I:/mdwiki/mdwiki/public_html/td/Tables/jsons/words.json";
    }
    try {
        $file = file_get_contents($word_file);
        // $file = file_get_contents("https://mdwiki.toolforge.org/td/Tables/jsons/words.json");
        $Words_table = json_decode($file, true);
    } catch (\Exception $e) {
        $Words_table = [];
    }
    return $Words_table;
}

function handleNoAccess($user, $tab, $rand_id)
{
    $error = ['code' => 'noaccess', 'info' => 'noaccess'];
    $editit = ['error' => $error, 'edit' => ['error' => $error, 'username' => $user], 'username' => $user];
    $tab['result_to_cx'] = $editit;

    to_do($tab, "noaccess", $rand_id);
    InsertPublishReports($tab['title'], $user, $tab['lang'], $tab['sourcetitle'], "noaccess", $tab);

    pub_test_print("\n<br>");
    pub_test_print("\n<br>");

    print(json_encode($editit, JSON_PRETTY_PRINT));
}

function start($request)
{
    $rand_id = time() .  "-" . bin2hex(random_bytes(6));
    $user = formatUser($request['user'] ?? '');
    $title = formatTitle($request['title'] ?? '');
    $tab = [
        'title' => $title,
        'summary' => "",
        'lang' => $request['target'] ?? '',
        'user' => $user,
        'campaign' => $request['campaign'] ?? '',
        'result' => "",
        'words' => "",
        'edit' => [],
        'sourcetitle' => $request['sourcetitle'] ?? ''
    ];
    $access = get_user_access($user);

    if (empty($access)) {
        handleNoAccess($user, $tab, $rand_id);
        return;
    }

    $Words_table = load_words_table();
    $tab['words'] = $Words_table[$title] ?? 0;

    $tr_type = $request['tr_type'] ?? 'lead';

    $text = $request['text'] ?? '';
    $revid = get_revid($tab['sourcetitle']);
    if (empty($revid)) $revid = get_revid_db($tab['sourcetitle']);

    if (empty($revid)) {
        $tab['empty revid'] = 'Can not get revid from all_pages_revids.json';
        $revid = $request['revid'] ?? $request['revision'] ?? '';
    }

    $tab['revid'] = $revid;

    $hashtag = determineHashtag($tab['title'], $user);
    $tab['summary'] = make_summary($revid, $tab['sourcetitle'], $tab['lang'], $hashtag);


    $newtext = text_changes($tab['sourcetitle'], $tab['title'], $text, $tab['lang'], $revid);

    if (!empty($newtext)) {
        $tab['fix_refs'] = ($newtext != $text) ? 'yes' : 'no';
        $text = $newtext;
    }

    $edit_result = processEdit($request, $access, $text, $user, $tab, $rand_id, $tr_type);

    pub_test_print("\n<br>");
    pub_test_print("\n<br>");

    print(json_encode($edit_result, JSON_PRETTY_PRINT));
}
