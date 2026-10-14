<?php

namespace Publish\Process;

use Publish\MdwikiSql\PublishReportsRepository;

use Publish\Process\ProcessEdit;
use Publish\Process\TextChanger;

use Publish\DTO\Result;
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
        $requestObj = PageRequest::fromArray($request);

        $randId = $this->generateRequestId();
        $tab = $this->buildInitialTab($requestObj);

        $access = $this->accessHelps->getAccess($requestObj->user);
        if (empty($access)) {
            return $this->handleNoAccess($tab, $randId);
        }

        $tab = $this->enrichTab($tab, $requestObj);
        $text = $this->prepareText($requestObj);
        $tab['fix_refs'] = ($requestObj->text != $text) ? 'yes' : 'no';

        return $this->processEdit->handle(
            $requestObj->toArray(),
            $access,
            $text,
            $requestObj->user,
            $tab,
            $randId,
            $requestObj->trType
        );
    }

    /** Add words count, revid and summary to the tab. */
    private function enrichTab(array $tab, PageRequest $request): array
    {
        $titleData = $this->accessHelps->loadTitleData($request->title);

        $words = 0;
        if ($titleData) {
            $words = $request->trType === "lead"
                ? $titleData['w_lead_words'] ?? 0
                : $titleData['w_all_words'] ?? 0;
        }

        $tab['words'] = $words;

        if (empty($request->revid)) {
            $request->revid = $titleData['revid'] ?? 0;
        }

        if (empty($request->revid)) {
            $tab['empty revid'] = 'Can not get revid';
        }
        $tab['revid'] = $request->revid;

        $hashtag = StartUtils::determineHashtag($request->title, $request->user);
        $tab['summary'] = StartUtils::make_summary(
            $request->revid,
            $request->sourcetitle,
            $request->target,
            $hashtag
        );

        return $tab;
    }

    /** Apply text fixes (refs, ...) and record whether anything changed. */
    private function prepareText(PageRequest $request): string
    {
        $newText = $this->editProcess->text_changes(
            $request->sourcetitle,
            $request->title,
            $request->text,
            $request->target,
            $request->revid,
        );

        if (empty($newText)) {
            return $request->text;
        }
        return $newText;
    }
    private function buildInitialTab(PageRequest $request): array
    {
        return [
            'title'        => $request->title,
            'lang'         => $request->target,
            'user'         => $request->user,
            'campaign'     => $request->campaign,
            'sourcetitle'  => $request->sourcetitle,

            'request'      => $request->toArray(),
            'edit'         => [],
            'result_to_cx' => [],
            'summary'      => "",
            'result'       => "",
            'words'        => "",
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

    private function handleNoAccess(array $tab, string $randId): array
    {
        $error = ['code' => 'noaccess', 'info' => 'noaccess'];
        $response = [
            'error'    => $error,
            'edit'     => ['error' => $error, 'username' => $tab['user']],
            'username' => $tab['user'],
        ];
        $tab['result_to_cx'] = $response;

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
