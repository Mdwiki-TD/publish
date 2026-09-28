<?php

namespace Publish\Process;

use Publish\AddToDb\PublishReportsRepository;
use Publish\Process\ProcessEdit;

use function Publish\EditProcess\text_changes;
use function Publish\Helps\pub_test_print;
use function Publish\AccessHelps\get_access_from_db;
use function Publish\FilesHelps\to_do;

use function Publish\Revids\get_revid_db;
use function Publish\Revids\get_revid;

use function Publish\StartUtils\make_summary;
use function Publish\StartUtils\formatTitle;
use function Publish\StartUtils\formatUser;
use function Publish\StartUtils\determineHashtag;

/**
 * Front controller for the publish flow.
 *
 * Responsibilities (orchestration only, no business logic):
 *   1. read & normalise the incoming request
 *   2. authorise the user (OAuth access)
 *   3. prepare the edit context (words, revid, summary, text)
 *   4. delegate the actual edit to ProcessEdit
 *   5. render the response
 */
class StartController
{
    private const DEFAULT_TR_TYPE = 'lead';
    private const WORDS_FILE = 'jsons/words.json';

    private ProcessEdit $processEdit;

    public function __construct(?ProcessEdit $processEdit = null)
    {
        $this->processEdit = $processEdit ?? new ProcessEdit();
    }

    // ------------------------------------------------------------
    // Entry points
    // ------------------------------------------------------------

    /** Handle the request and print the JSON response. */
    public function run(array $request): void
    {
        $this->render($this->handle($request));
    }

    /** Handle the request and return the response (no output, easy to test). */
    public function handle(array $request): array
    {
        $randId = $this->generateRequestId();
        $user   = formatUser($request['user'] ?? '');
        $tab    = $this->buildInitialTab($request, $user);

        $access = get_access_from_db($user);
        if (empty($access)) {
            return $this->handleNoAccess($user, $tab, $randId);
        }

        $tab  = $this->enrichTab($tab, $request, $user);
        $text = $this->prepareText($tab, $request['text'] ?? '');

        $trType = $request['tr_type'] ?? self::DEFAULT_TR_TYPE;

        return $this->processEdit->handle($request, $access, $text, $user, $tab, $randId, $trType);
    }

    /** Add words count, revid and summary to the tab. */
    private function enrichTab(array $tab, array $request, string $user): array
    {
        $wordsTable = $this->loadWordsTable();
        $tab['words'] = $wordsTable[$tab['title']] ?? 0;

        [$revid, $revidMissing] = $this->resolveRevid($tab['sourcetitle'], $request);
        if ($revidMissing) {
            $tab['empty revid'] = 'Can not get revid from all_pages_revids.json';
        }
        $tab['revid'] = $revid;

        $hashtag = determineHashtag($tab['title'], $user);
        $tab['summary'] = make_summary($revid, $tab['sourcetitle'], $tab['lang'], $hashtag);

        return $tab;
    }

    /**
     * @return array{0: mixed, 1: bool} [revid, wasMissingFromSources]
     */
    private function resolveRevid(string $sourcetitle, array $request): array
    {
        $revid = get_revid($sourcetitle);
        if (empty($revid)) {
            $revid = get_revid_db($sourcetitle);
        }
        if (!empty($revid)) {
            return [$revid, false];
        }
        $revid = $request['revid'] ?? $request['revision'] ?? '';
        return [$revid, true];
    }

    /** Apply text fixes (refs, ...) and record whether anything changed. */
    private function prepareText(array &$tab, string $text): string
    {
        $newText = text_changes($tab['sourcetitle'], $tab['title'], $text, $tab['lang'], $tab['revid']);

        if (empty($newText)) {
            return $text;
        }
        $tab['fix_refs'] = ($newText != $text) ? 'yes' : 'no';
        return $newText;
    }
    private function buildInitialTab(array $request, string $user): array
    {
        return [
            'title'       => formatTitle($request['title'] ?? ''),
            'summary'     => "",
            'lang'        => $request['target'] ?? '',
            'user'        => $user,
            'campaign'    => $request['campaign'] ?? '',
            'result'      => "",
            'words'       => "",
            'edit'        => [],
            'sourcetitle' => $request['sourcetitle'] ?? '',
        ];
    }

    // ------------------------------------------------------------
    // Request preparation
    // ------------------------------------------------------------

    private function generateRequestId(): string
    {
        return time() . "-" . bin2hex(random_bytes(6));
    }

    // ------------------------------------------------------------
    // Words table
    // ------------------------------------------------------------

    private function resolveTablesPath(): string
    {
        $path = getenv("TABLES_PATH") !== false ? getenv("TABLES_PATH") : ($_ENV["TABLES_PATH"] ?? "");
        if (!empty($path)) {
            return $path;
        }
        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? "");
        return $home . "/public_html/td/Tables/";
    }

    private function loadWordsTable(): array
    {
        $wordFile = $this->resolveTablesPath() . "/" . self::WORDS_FILE;

        try {
            $content = file_get_contents($wordFile);
            // $content = file_get_contents("https://mdwiki.toolforge.org/td/Tables/jsons/words.json");

            $table = $content === false ? [] : json_decode($content, true);
        } catch (\Exception $e) {
            $table = [];
        }
        return is_array($table) ? $table : [];
    }

    // ------------------------------------------------------------
    // Error / response handling
    // ------------------------------------------------------------

    private function handleNoAccess(string $user, array $tab, string $randId): array
    {
        $error = ['code' => 'noaccess', 'info' => 'noaccess'];
        $response = [
            'error'    => $error,
            'edit'     => ['error' => $error, 'username' => $user],
            'username' => $user,
        ];
        $tab['result_to_cx'] = $response;

        to_do($tab, "noaccess", $randId);

        $repository = new PublishReportsRepository();
        $repository->insertPublishReports(
            $tab['title'],
            $user,
            $tab['lang'],
            $tab['sourcetitle'],
            "noaccess",
            $tab
        );

        return $response;
    }

    private function render(array $response): void
    {
        pub_test_print("\n<br>");
        pub_test_print("\n<br>");

        print(json_encode($response, JSON_PRETTY_PRINT));
    }
}
