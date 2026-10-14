<?php

namespace Publish\Process;

use Publish\MdwikiSql\PublishReportsRepository;

use Publish\Process\ProcessEdit;
use Publish\Process\TextChanger;

use Publish\DTO\PageRequest;
use Publish\Utils\FilesHelps;
use Publish\Logger;
use Publish\MdwikiSql\AccessHelps;
use Publish\Utils\StartUtils;

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

    private ProcessEdit              $processEdit;
    private TextChanger              $editProcess;
    private AccessHelps              $accessHelps;
    private PublishReportsRepository $repository;

    public function __construct(
        ?ProcessEdit $processEdit = null,
        ?TextChanger $editProcess = null,
        ?PublishReportsRepository $repository = null,
        ?AccessHelps $accessHelps = null,
    ) {
        $this->processEdit = $processEdit ?? new ProcessEdit();
        $this->editProcess = $editProcess ?? new TextChanger();
        $this->repository = $repository ?? new PublishReportsRepository();
        $this->accessHelps = $accessHelps ?? AccessHelps::getInstance();

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
        $user = StartUtils::formatUser($request['user'] ?? '');
        $tab = $this->buildInitialTab($request, $user);

        $access = $this->accessHelps->getAccess($user);
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
        $titleData = $this->accessHelps->loadTitleData($tab['title']);

        $words = 0;
        if ($titleData) {
            $words = $trType === "lead"
                ? $titleData['w_lead_words'] ?? 0
                : $titleData['w_all_words'] ?? 0;
        }

        $tab['words'] = $words;

        $revid = $request['revid'] ?? $request['revision'] ?? '';
        if (empty($revid)) {
            $revid = $titleData['revid'] ?? 0;
        }
        if (empty($revid)) {
            $tab['empty revid'] = 'Can not get revid';
        }
        $tab['revid'] = $revid;

        $hashtag = StartUtils::determineHashtag($tab['title'], $user);
        $tab['summary'] = StartUtils::make_summary($revid, $tab['sourcetitle'], $tab['lang'], $hashtag);

        return $tab;
    }

    /** Apply text fixes (refs, ...) and record whether anything changed. */
    private function prepareText(array &$tab, string $text): string
    {
        $newText = $this->editProcess->text_changes($tab['sourcetitle'], $tab['title'], $text, $tab['lang'], $tab['revid']);

        if (empty($newText)) {
            return $text;
        }
        $tab['fix_refs'] = ($newText != $text) ? 'yes' : 'no';
        return $newText;
    }
    private function buildInitialTab(array $request, string $user): array
    {
        return [
            'title'       => StartUtils::formatTitle($request['title'] ?? ''),
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
        $tab['user'] = $user;

        FilesHelps::to_do($tab, "noaccess", $randId);

        $this->repository->insertPublishReports(
            $tab,
            "noaccess",
        );

        return $response;
    }

    private function render(array $response): void
    {
        Logger::debug("\n<br>");
        Logger::debug("\n<br>");

        print (json_encode($response, JSON_PRETTY_PRINT));
    }
}
