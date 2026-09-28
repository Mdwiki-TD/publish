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

class Start
{
    private ProcessEdit $processEdit;

    public function __construct(?ProcessEdit $processEdit = null)
    {
        $this->processEdit = $processEdit ?? new ProcessEdit();
    }

    public function loadWordsTable(): array
    {
        $tablesPath = getenv("TABLES_PATH") !== false ? getenv("TABLES_PATH") : ($_ENV["TABLES_PATH"] ?? "");
        if (empty($tablesPath)) {
            $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? "");
            $tablesPath = $home . "/public_html/td/Tables/";
        }

        $wordFile = "$tablesPath/jsons/words.json";

        try {
            $file = file_get_contents($wordFile);
            // $file = file_get_contents("https://mdwiki.toolforge.org/td/Tables/jsons/words.json");
            $wordsTable = json_decode($file, true);
        } catch (\Exception $e) {
            $wordsTable = [];
        }
        return is_array($wordsTable) ? $wordsTable : [];
    }

    public function handleNoAccess($user, array $tab, string $randId): void
    {
        $error = ['code' => 'noaccess', 'info' => 'noaccess'];
        $editit = [
            'error' => $error,
            'edit' => ['error' => $error, 'username' => $user],
            'username' => $user
        ];
        $tab['result_to_cx'] = $editit;

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

        pub_test_print("\n<br>");
        pub_test_print("\n<br>");

        print(json_encode($editit, JSON_PRETTY_PRINT));
    }

    public function run(array $request): void
    {
        $randId = time() . "-" . bin2hex(random_bytes(6));
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

        $access = get_access_from_db($user);

        if (empty($access)) {
            $this->handleNoAccess($user, $tab, $randId);
            return;
        }

        $wordsTable = $this->loadWordsTable();
        $tab['words'] = $wordsTable[$title] ?? 0;

        $trType = $request['tr_type'] ?? 'lead';
        $text = $request['text'] ?? '';

        $revid = get_revid($tab['sourcetitle']);
        if (empty($revid)) {
            $revid = get_revid_db($tab['sourcetitle']);
        }

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

        $editResult = $this->processEdit->handle($request, $access, $text, $user, $tab, $randId, $trType);

        pub_test_print("\n<br>");
        pub_test_print("\n<br>");

        print(json_encode($editResult, JSON_PRETTY_PRINT));
    }
}
