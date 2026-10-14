<?php

namespace Publish\Process;

use Publish\MdwikiSql\PublishReportsRepository;

use Publish\Process\ProcessEdit;
use Publish\Process\TextChanger;

use Publish\DTO\Result;
use Publish\DTO\Request;
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
        $requestObj = Request::fromArray($request);

        $randId = $this->generateRequestId();
        $result = Result::fromRequest($requestObj);

        $access = $this->accessHelps->getAccess($requestObj->user);
        if (empty($access)) {
            return $this->handleNoAccess($result, $randId);
        }

        $this->enrichResult($result, $requestObj);
        $text = $this->prepareText($requestObj);
        $result->fixRefs = ($requestObj->text != $text) ? 'yes' : 'no';

        return $this->processEdit->handle(
            $requestObj->toArray(),
            $access,
            $text,
            $result->toArray(),   // TODO: pass $result once ProcessEdit is migrated
            $randId,
        );
    }

    /** Add words count, revid and summary to the result. */
    private function enrichResult(Result $result, Request $request): void
    {
        $titleData = $this->accessHelps->loadTitleData($request->title);

        $words = 0;
        if ($titleData) {
            $words = $request->trType === "lead"
                ? $titleData['w_lead_words'] ?? 0
                : $titleData['w_all_words'] ?? 0;
        }
        $result->words = $words;

        if (empty($request->revid)) {
            $request->revid = $titleData['revid'] ?? 0;
        }

        if (empty($request->revid)) {
            $result->emptyRevid = 'Can not get revid';
        }
        $result->revid = $request->revid;

        $hashtag = StartUtils::determineHashtag($request->title, $request->user);
        $result->summary = StartUtils::make_summary(
            $request->revid,
            $request->sourcetitle,
            $request->target,
            $hashtag
        );
    }

    /** Apply text fixes (refs, ...) and record whether anything changed. */
    private function prepareText(Request $request): string
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

    private function handleNoAccess(Result $result, string $randId): array
    {
        $error = ['code' => 'noaccess', 'info' => 'noaccess'];
        $response = [
            'error'    => $error,
            'edit'     => ['error' => $error, 'username' => $result->user],
            'username' => $result->user,
        ];
        $result->resultToCx = $response;

        $tab = $result->toArray();
        FilesHelps::to_do($tab, "noaccess", $randId);
        $this->repository->insertPublishReports($tab, "noaccess");

        return $response;
    }

    private function render(array $response): void
    {
        Logger::debug("\n<br>");
        Logger::debug("\n<br>");

        print (json_encode($response, JSON_PRETTY_PRINT));
    }
}
