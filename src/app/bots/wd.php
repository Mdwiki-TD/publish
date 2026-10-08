<?php

namespace Publish\WD;

use Publish\MediaWikiClient\MediaWikiEditClient;

use function Publish\AccessHelps\get_access_from_db;
use function Publish\Helps\pub_test_print;
use Publish\MdwikiSql\Database;

function GetQidForMdtitle($title)
{
    $db = new Database();

    return $db->fetchQuery("SELECT qid FROM qids WHERE title = ?", [$title]);
}

function getAccessCredentials(string $user, string $accessKey, string $accessSecret): array|null
{
    if ($accessKey && $accessSecret) {
        return [$accessKey, $accessSecret];
    }

    $access = get_access_from_db($user);

    if (empty($access)) {
        pub_test_print("user = $user");
        pub_test_print("access == null");
        return null;
    }

    $accessKey = $access['access_key'];
    $accessSecret = $access['access_secret'];

    return [$accessKey, $accessSecret];
}

function LinkIt(array $apiParams, string $accessKey, string $accessSecret): array
{
    $wikidataDomain = getenv('WIKIDATA_DOMAIN') ?: ($_ENV['WIKIDATA_DOMAIN'] ?? 'www.wikidata.org');
    $httpsDomain = "https://$wikidataDomain";

    $editClient = new MediaWikiEditClient();
    $response = $editClient->postParams(
        (array) $apiParams,
        (string) $httpsDomain,
        (string) $accessKey,
        (string) $accessSecret,
    );

    $result = json_decode($response, true);
    if (!is_array($result)) {
        $result = [];
    }
    if (isset($result['error'])) {
        pub_test_print("postParams: Result->error: " . json_encode($result['error']));
    }

    if (empty($result)) {
        pub_test_print("postParams: Error: " . json_last_error() . " " . json_last_error_msg());
        pub_test_print("response:");
        pub_test_print($response);
    }
    return $result;
}
function LinkToWikidata(string $sourcetitle, string $lang, string $user, string $targettitle, array $access): array
{
    $accessKey = $access['access_key'];
    $accessSecret = $access['access_secret'];

    $qids = GetQidForMdtitle($sourcetitle);
    $qid = $qids[0]['qid'] ?? '';

    $credentials = getAccessCredentials($user, $accessKey, $accessSecret);
    if ($credentials === null) {
        return ['error' => 'Access credentials not found for user: ' . $user, 'qid' => $qid];
    }
    list($accessKey, $accessSecret) = $credentials;

    $apiParams = [
        "action" => "wbsetsitelink",
        "linktitle" => $targettitle,
        "linksite" => "{$lang}wiki",
    ];
    if (!empty($qid)) {
        $apiParams["id"] = $qid;
    } else {
        $apiParams["title"] = $sourcetitle;
        $apiParams["site"] = "enwiki";
    }

    $linkResult = LinkIt($apiParams, $accessKey, $accessSecret);

    $linkResult["qid"] = $qid;

    if (isset($linkResult['success']) && $linkResult['success']) {
        pub_test_print("success: true");
        return ['result' => "success", 'qid' => $qid];
    }

    return $linkResult;
}
