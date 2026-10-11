<?php
namespace Publish\Api;

use Publish\Logger;
use Publish\Settings;
use Publish\MdwikiSql\DatabaseInterface;
use Publish\MdwikiSql\Database;
use Publish\MdwikiSql\AccessHelps;
use Publish\MediaWikiClient\MediaWikiEditClient;

class WdApi
{
    public string               $wikidataDomain;
    private DatabaseInterface   $db;
    private MediaWikiEditClient $editClient;
    private AccessHelps         $accessHelps;
    private static ?self        $instance       = null;

    public function __construct(
        ?DatabaseInterface $db = null,
        ?MediaWikiEditClient $editClient = null,
        ?AccessHelps $accessHelps = null,
    ) {
        $this->db = $db ?? new Database();
        $this->editClient = $editClient ?? new MediaWikiEditClient();
        $this->accessHelps = $accessHelps ?? AccessHelps::getInstance();

        $settings = Settings::getInstance();
        $this->wikidataDomain = $settings->wikidataDomain;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    private function GetQidForMdtitle($title)
    {
        return $this->db->fetchQuery("SELECT qid FROM qids WHERE title = ?", [$title]);
    }

    private function LinkIt(
        array $apiParams,
        string $accessKey,
        string $accessSecret
    ): array {
        $httpsDomain = "https://{$this->wikidataDomain}";

        $response = $this->editClient->postParams(
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
    public function getAccessCredentials(
        string $user,
        string $accessKey,
        string $accessSecret
    ): array|null {
        if ($accessKey && $accessSecret) {
            return [$accessKey, $accessSecret];
        }
        $access = $this->accessHelps->getAccess($user);

        if (empty($access)) {
            Logger::debug("user = $user");
            Logger::debug("access == null");
            return null;
        }

        $accessKey = $access['access_key'];
        $accessSecret = $access['access_secret'];

        return [$accessKey, $accessSecret];
    }

    public function LinkToWikidata(
        string $sourcetitle,
        string $lang,
        string $user,
        string $targettitle,
        array $access
    ): array {
        $accessKey = $access['access_key'];
        $accessSecret = $access['access_secret'];

        $qids = $this->GetQidForMdtitle($sourcetitle);
        $qid = $qids[0]['qid'] ?? '';

        $credentials = $this->getAccessCredentials($user, $accessKey, $accessSecret);
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

        $linkResult = $this->LinkIt($apiParams, $accessKey, $accessSecret);

        $linkResult["qid"] = $qid;

        if (isset($linkResult['success']) && $linkResult['success']) {
            Logger::debug("success: true");
            return ['result' => "success", 'qid' => $qid];
        }

        return $linkResult;
    }
}
