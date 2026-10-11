<?php

namespace Publish\Process;

use Publish\AddToDb\PublishReportsRepository;
use Publish\Process\ProcessEdit;

use function Publish\EditProcess\text_changes;
use function Publish\Helps\pub_test_print;
use function Publish\FilesHelps\to_do;

use Publish\MdwikiSql\AccessHelps;
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
        $trType = $request['tr_type'] ?? self::DEFAULT_TR_TYPE;

        $randId = $this->generateRequestId();
        $user = formatUser($request['user'] ?? '');
        $tab = $this->buildInitialTab($request, $user);

        $access = AccessHelps::getAccess($user);
        if (empty($access)) {
            return $this->handleNoAccess($user, $tab, $randId);
        }

        $tab = $this->enrichTab($tab, $request, $user, $trType);
        $text = $this->prepareText($tab, $request['text'] ?? '');

        return $this->processEdit->handle($request, $access, $text, $user, $tab, $randId, $trType);
    }

    /** Add words count, revid and summary to the tab. */
    private function enrichTab(array $tab, array $request, string $user, string $trType): array
    {
        $titleData = AccessHelps::loadTitleData($tab['title']);

        $words = 0;
        if ($titleData) {
            $words = $trType === "lead"
                ? $titleData['w_lead_words'] ?? 0
                : $titleData['w_tr_words'] ?? 0;
        }

        $tab['words'] = $words;

        $revid = $request['revid'] ?? $request['revision'] ?? '';
        if (empty($revid)) {
            $revid = $titleData['revid'] ?? 0;
        }
        if (empty($revid)) {
            $tab['empty revid'] = 'Can not get revid from all_pages_revids.json';
        }
        $tab['revid'] = $revid;

        $hashtag = determineHashtag($tab['title'], $user);
        $tab['summary'] = make_summary($revid, $tab['sourcetitle'], $tab['lang'], $hashtag);

        return $tab;
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

        print (json_encode($response, JSON_PRETTY_PRINT));
    }
}
