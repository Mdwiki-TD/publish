<?php
namespace Publish\Api;

use Publish\Logger;
use Publish\MdwikiSql\Database;
use Publish\MdwikiSql\AccessHelps;
use Publish\MediaWikiClient\MediaWikiEditClient;

class WdApi
{
    public static function GetQidForMdtitle($title)
    {
        $db = new Database();

        return $db->fetchQuery("SELECT qid FROM qids WHERE title = ?", [$title]);
    }

    public static function getAccessCredentials(string $user, string $accessKey, string $accessSecret): array|null
    {
        if ($accessKey && $accessSecret) {
            return [$accessKey, $accessSecret];
        }

        $access = AccessHelps::getAccess($user);

        if (empty($access)) {
            Logger::debug("user = $user");
            Logger::debug("access == null");
            return null;
        }

        $accessKey = $access['access_key'];
        $accessSecret = $access['access_secret'];

        return [$accessKey, $accessSecret];
    }

    public static function LinkIt(array $apiParams, string $accessKey, string $accessSecret): array
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
            Logger::debug("postParams: Result->error: " . json_encode($result['error']));
        }

        if (empty($result)) {
            Logger::debug("postParams: Error: " . json_last_error() . " " . json_last_error_msg());
            Logger::debug("response:");
            Logger::debug($response);
        }
        return $result;
    }
    public static function LinkToWikidata(string $sourcetitle, string $lang, string $user, string $targettitle, array $access): array
    {
        $accessKey = $access['access_key'];
        $accessSecret = $access['access_secret'];

        $qids = self::GetQidForMdtitle($sourcetitle);
        $qid = $qids[0]['qid'] ?? '';

        $credentials = getAccessCredentials($user, $accessKey, $accessSecret);
        if ($credentials === null) {
            return ['error' => 'Access credentials not found for user: ' . $user, 'qid' => $qid];
        }
        list($accessKey, $accessSecret) = $credentials;

        $apiParams = [
            "action"    => "wbsetsitelink",
            "linktitle" => $targettitle,
            "linksite"  => "{$lang}wiki",
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
            Logger::debug("success: true");
            return ['result' => "success", 'qid' => $qid];
        }

        return $linkResult;
    }
}
