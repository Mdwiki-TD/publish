<?php

namespace Publish\Process;

use MediaWiki\OAuthClient\Token;

use Publish\AddToDb\PublishReportsRepository;
use Publish\MediaWikiClient\MediaWikiEditClient;
use Publish\Process\EditProcessLog;

use function Publish\Helps\pub_test_print;
use function Publish\WD\LinkToWikidata;
use function Publish\FilesHelps\to_do;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\WikiApi\GetTitleInfo;
use function Publish\StartUtils\get_errors_file;

class ProcessEdit
{
    private const FALLBACK_USER = 'Mr. Ibrahem';
    private const USER_TABLE_FILTER = 'abusefilter-warning-39';

    private EditProcessLog $editProcessLog;

    public function __construct(?EditProcessLog $editProcessLog = null)
    {
        $this->editProcessLog = $editProcessLog ?? new EditProcessLog();
    }

    public function shouldAddedToWikidata(string $lang, string $title): bool
    {
        $pageInformations = GetTitleInfo($title, $lang);
        if (!$pageInformations) {
            return false;
        }
        $pageNamespace = $pageInformations["ns"] ?? null;
        // skip link to wd for user pages
        return $pageNamespace != 2;
    }

    public function retryWithFallbackUser($sourcetitle, $lang, $title, $user): array
    {
        $linkTowd = [];
        pub_test_print("getCsrfTokenData failed for user: $user, retrying with " . self::FALLBACK_USER);

        // Get fresh credentials from database
        $fallbackAccess = get_access_from_db(self::FALLBACK_USER);

        if (!empty($fallbackAccess)) {
            $linkTowd = LinkToWikidata($sourcetitle, $lang, self::FALLBACK_USER, $title, $fallbackAccess);

            // Add a note that fallback was used
            if (!isset($linkTowd['error'])) {
                $linkTowd['fallback_user'] = self::FALLBACK_USER;
                $linkTowd['original_user'] = $user;
                pub_test_print("Successfully linked using " . self::FALLBACK_USER . " fallback credentials");
            }
        }
        return $linkTowd;
    }

    public function handleSuccessfulEdit($sourcetitle, $lang, $user, $title, $access, $randId): array
    {
        if (!$this->shouldAddedToWikidata($lang, $title)) {
            // skip link to wd for user pages
            return ["error" => "skip link to wd for user pages"];
        }
        $linkTowd = [];

        try {
            $linkTowd = LinkToWikidata($sourcetitle, $lang, $user, $title, $access);
            // Check if the error is getCsrfTokenData failure and user is not already the fallback user
            if (
                isset($linkTowd['error'])
                && $linkTowd['error'] == 'get_csrftoken failed'
                && $user !== self::FALLBACK_USER
            ) {
                $linkTowd['fallback'] = $this->retryWithFallbackUser($sourcetitle, $lang, $title, $user);
            }
            // Log errors if they still exist after retry
        } catch (\Exception $e) {
            pub_test_print($e->getMessage());
        }

        if (isset($linkTowd['error'])) {
            $tab3 = [
                'error' => $linkTowd['error'],
                'qid' => $linkTowd['qid'] ?? "",
                'title' => $title,
                'sourcetitle' => $sourcetitle,
                'fallback' => $linkTowd['fallback'] ?? "",
                'lang' => $lang,
                'username' => $user
            ];
            // if str($linkTowd['error']) has "Links to user pages"  then file_name='wd_user_pages' else 'wd_errors'
            $fileName = get_errors_file($linkTowd['error'], "wd_errors");
            to_do($tab3, $fileName, $randId);

            $repository = new PublishReportsRepository();
            $repository->insertPublishReports(
                $title,
                $user,
                $lang,
                $sourcetitle,
                $fileName,
                $tab3
            );
        }
        return $linkTowd;
    }

    public static function prepareApiParams($title, $summary, $text, $request): array
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

    // ------------------------------------------------------------
    // Main flow
    // ------------------------------------------------------------

    public function handle($request, $access, $text, $user, $tab, $randId, $trType): array
    {
        $apiParams = $this->prepareApiParams($tab['title'], $tab['summary'], $text, $request);

        $editit = $this->publishEdit($apiParams, $tab['lang'], $access);

        $tab['result'] = $editit['edit']['result'] ?? '';

        if ($tab['result'] === 'Success') {
            $editit = $this->onSuccess($editit, $tab, $user, $access, $randId, $trType);
            $toDoFile = "success";
        } elseif ($editit['edit']['captcha'] ?? null) {
            $toDoFile = "captcha";
        } else {
            $toDoFile = get_errors_file($editit, "errors");
        }

        $tab['result_to_cx'] = $editit;

        $this->logResult($tab, $user, $toDoFile, $randId);

        return $editit;
    }

    private function logResult(array $tab, $user, string $toDoFile, $randId): void
    {
        to_do($tab, $toDoFile, $randId);

        $repository = new PublishReportsRepository();
        $repository->insertPublishReports(
            $tab['title'],
            $user,
            $tab['lang'],
            $tab['sourcetitle'],
            $toDoFile,
            $tab
        );
    }

    private function publishEdit(array $apiParams, string $lang, array $access): array
    {
        $accessToken = new Token($access["access_key"], $access["access_secret"]);

        $editClient = new MediaWikiEditClient();
        return $editClient->publishEdit($apiParams, $lang, $accessToken);
    }

    /** Link to Wikidata, then record the page in the database. */
    private function onSuccess(array $editit, array $tab, $user, $access, $randId, $trType): array
    {
        $linkToWikidata = $this->handleSuccessfulEdit(
            $tab['sourcetitle'],
            $tab['lang'],
            $user,
            $tab['title'],
            $access,
            $randId
        );
        $editit['LinkToWikidata'] = $linkToWikidata;
        $toUsersTable = $this->shouldUseUsersTable($linkToWikidata);
        $editit['sql_result'] = $this->editProcessLog->addToDb(
            $tab['title'],
            $tab['lang'],
            $user,
            $toUsersTable,
            $tab['campaign'],
            $tab['sourcetitle'],
            (string)($tab['revid'] ?? ""),
            (string)$tab['words'],
            $trType
        );
        return $editit;
    }

    private function shouldUseUsersTable(array $linkToWikidata): bool
    {
        // if $wdResult has "abusefilter-warning-39" then $toUsersTable = true
        return strpos(json_encode($linkToWikidata), self::USER_TABLE_FILTER) !== false;
    }
}
