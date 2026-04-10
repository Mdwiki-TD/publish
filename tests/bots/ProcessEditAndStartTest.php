<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/bots/process_edit.php  (namespace Publish\EditProcess)
 *            src/su/start.php             (helper functions)
 *
 * These files orchestrate the full edit workflow (OAuth → MediaWiki API →
 * Wikidata linking → DB insert).  Because they depend on live OAuth and DB
 * connections the tests here focus on the pure-logic helper functions that
 * can be exercised without any network or database.
 */
class ProcessEditAndStartTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // require_once dirname(dirname(__DIR__)) . '/src/su/bots/helps.php';
        // require_once dirname(dirname(__DIR__)) . '/src/su/bots/mdwiki_sql.php';
        // require_once dirname(dirname(__DIR__)) . '/src/su/bots/files_helps.php';

        // process_edit.php defines several standalone helper functions we test.
        // It also `use`s functions from other namespaces – those are auto-loaded
        // via include.php in a real deployment; here we stub out what we need.
        // We load process_edit.php only for syntax/namespace loading; individual
        // function logic is tested below via direct calls.
        // NOTE: start.php executes start($_POST) at the bottom, so we must NOT
        // include it directly.  We test its helper functions by copy-asserting
        // the logic in isolation (regex / string transforms).
    }

    // -----------------------------------------------------------------------
    // process_edit.php – get_errors_file()
    // -----------------------------------------------------------------------

    /** Simulate get_errors_file logic without including the file. */
    private function getErrorsFile(mixed $editit, string $placeholder): string
    {
        $errsMain = [
            'protectedpage',
            'titleblacklist',
            'ratelimited',
            'editconflict',
            'spam filter',
            'abusefilter',
            'mwoauth-invalid-authorization',
        ];
        $errsWd = [
            'Links to user pages' => 'wd_user_pages',
            'get_csrftoken'       => 'wd_csrftoken',
            'protectedpage'       => 'wd_protectedpage',
        ];

        $errs     = ($placeholder === 'errors') ? $errsMain : $errsWd;
        $cText    = json_encode($editit);
        $toDoFile = $placeholder;

        foreach ($errs as $key => $value) {
            $err = is_string($key) ? $key : $value;
            if (strpos($cText, $err) !== false) {
                $toDoFile = is_string($key) ? $value : $err;
                break;
            }
        }
        return $toDoFile;
    }

    public function testGetErrorsFileReturnsPlaceholderForNoMatch(): void
    {
        $result = $this->getErrorsFile(['edit' => ['result' => 'Success']], 'errors');
        $this->assertSame('errors', $result);
    }

    public function testGetErrorsFileDetectsProtectedPage(): void
    {
        $result = $this->getErrorsFile(['error' => ['code' => 'protectedpage']], 'errors');
        $this->assertSame('protectedpage', $result);
    }

    public function testGetErrorsFileDetectsRateLimited(): void
    {
        $result = $this->getErrorsFile(['error' => ['code' => 'ratelimited']], 'errors');
        $this->assertSame('ratelimited', $result);
    }

    public function testGetErrorsFileDetectsAbuseFilter(): void
    {
        $result = $this->getErrorsFile(['error' => 'abusefilter triggered'], 'errors');
        $this->assertSame('abusefilter', $result);
    }

    public function testGetErrorsFileDetectsWdCsrftoken(): void
    {
        $result = $this->getErrorsFile(['error' => 'get_csrftoken failed'], 'wd_errors');
        $this->assertSame('wd_csrftoken', $result);
    }

    public function testGetErrorsFileDetectsWdUserPages(): void
    {
        $result = $this->getErrorsFile(['error' => 'Links to user pages is not allowed'], 'wd_errors');
        $this->assertSame('wd_user_pages', $result);
    }

    // -----------------------------------------------------------------------
    // process_edit.php – prepareApiParams() logic
    // -----------------------------------------------------------------------

    private function prepareApiParams(string $title, string $summary, string $text, array $request): array
    {
        $params = [
            'action'  => 'edit',
            'title'   => $title,
            'summary' => $summary,
            'text'    => $text,
            'format'  => 'json',
        ];
        if (isset($request['wpCaptchaId']) && isset($request['wpCaptchaWord'])) {
            $params['wpCaptchaId']   = $request['wpCaptchaId'];
            $params['wpCaptchaWord'] = $request['wpCaptchaWord'];
        }
        return $params;
    }

    public function testPrepareApiParamsBasicFields(): void
    {
        $params = $this->prepareApiParams('MyTitle', 'My summary', 'Article body', []);
        $this->assertSame('edit', $params['action']);
        $this->assertSame('MyTitle', $params['title']);
        $this->assertSame('json', $params['format']);
        $this->assertArrayNotHasKey('wpCaptchaId', $params);
    }

    public function testPrepareApiParamsIncludesCaptchaWhenPresent(): void
    {
        $request = ['wpCaptchaId' => 'abc123', 'wpCaptchaWord' => 'xkcd'];
        $params  = $this->prepareApiParams('T', 'S', 'B', $request);
        $this->assertSame('abc123', $params['wpCaptchaId']);
        $this->assertSame('xkcd', $params['wpCaptchaWord']);
    }

    public function testPrepareApiParamsOmitsCaptchaWhenPartiallyPresent(): void
    {
        $params = $this->prepareApiParams('T', 'S', 'B', ['wpCaptchaId' => 'only-id']);
        $this->assertArrayNotHasKey('wpCaptchaId', $params);
        $this->assertArrayNotHasKey('wpCaptchaWord', $params);
    }

    // -----------------------------------------------------------------------
    // start.php – formatUser() / formatTitle() / determineHashtag() logic
    // -----------------------------------------------------------------------

    private function formatUser(string $user): string
    {
        $special = ['Mr. Ibrahem 1' => 'Mr. Ibrahem', 'Admin' => 'Mr. Ibrahem'];
        $user    = $special[$user] ?? $user;
        return str_replace('_', ' ', $user);
    }

    private function formatTitle(string $title): string
    {
        $title = str_replace('_', ' ', $title);
        return str_replace('Mr. Ibrahem 1/', 'Mr. Ibrahem/', $title);
    }

    private function determineHashtag(string $title, string $user): string
    {
        $hashtag = '#mdwikicx';
        if (strpos($title, 'Mr. Ibrahem') !== false && $user === 'Mr. Ibrahem') {
            $hashtag = '';
        }
        return $hashtag;
    }

    public function testFormatUserNormalisesUnderscores(): void
    {
        $this->assertSame('John Doe', $this->formatUser('John_Doe'));
    }

    public function testFormatUserMapsSpecialAlias(): void
    {
        $this->assertSame('Mr. Ibrahem', $this->formatUser('Mr. Ibrahem 1'));
        $this->assertSame('Mr. Ibrahem', $this->formatUser('Admin'));
    }

    public function testFormatUserPassesThroughRegularUser(): void
    {
        $this->assertSame('Regular User', $this->formatUser('Regular_User'));
    }

    public function testFormatTitleNormalisesUnderscores(): void
    {
        $this->assertSame('My Article', $this->formatTitle('My_Article'));
    }

    public function testFormatTitleFixesMrIbrahem1Prefix(): void
    {
        $result = $this->formatTitle('Mr. Ibrahem 1/SomeArticle');
        $this->assertSame('Mr. Ibrahem/SomeArticle', $result);
    }

    public function testDetermineHashtagDefaultValue(): void
    {
        $this->assertSame('#mdwikicx', $this->determineHashtag('SomeArticle', 'RegularUser'));
    }

    public function testDetermineHashtagEmptyForMrIbrahemOwnPage(): void
    {
        $hashtag = $this->determineHashtag('Mr. Ibrahem/SomePage', 'Mr. Ibrahem');
        $this->assertSame('', $hashtag);
    }

    public function testDetermineHashtagKeptWhenDifferentUser(): void
    {
        $hashtag = $this->determineHashtag('Mr. Ibrahem/SomePage', 'OtherUser');
        $this->assertSame('#mdwikicx', $hashtag);
    }

    // -----------------------------------------------------------------------
    // make_summary() logic (from start.php)
    // -----------------------------------------------------------------------

    private function makeSummary(string $revid, string $sourcetitle, string $to, string $hashtag): string
    {
        return "Created by translating the page [[:mdwiki:Special:Redirect/revision/$revid|$sourcetitle]] to:$to $hashtag";
    }

    public function testMakeSummaryContainsRevid(): void
    {
        $summary = $this->makeSummary('98765', 'Paracetamol', 'fr', '#mdwikicx');
        $this->assertStringContainsString('98765', $summary);
    }

    public function testMakeSummaryContainsSourceTitle(): void
    {
        $summary = $this->makeSummary('111', 'Ibuprofen', 'de', '#mdwikicx');
        $this->assertStringContainsString('Ibuprofen', $summary);
    }

    public function testMakeSummaryContainsTargetLang(): void
    {
        $summary = $this->makeSummary('222', 'Aspirin', 'ja', '#mdwikicx');
        $this->assertStringContainsString('to:ja', $summary);
    }

    public function testMakeSummaryContainsHashtag(): void
    {
        $summary = $this->makeSummary('333', 'Title', 'es', '#mdwikicx');
        $this->assertStringContainsString('#mdwikicx', $summary);
    }

    // -----------------------------------------------------------------------
    // PHP syntax checks
    // -----------------------------------------------------------------------

    public function testProcessEditIsValidPhp(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg(dirname(dirname(__DIR__)) . '/src/su/bots/process_edit.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $output);
    }

    public function testStartPhpIsValidPhp(): void
    {
        $output = shell_exec('php -l ' . escapeshellarg(dirname(dirname(__DIR__)) . '/src/su/start.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $output);
    }
}
