<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

use Publish\Utils\StartUtils;

/**
 * Tests for StartUtils.php
 */
class StartUtilsTest extends TestCase
{
    public static function setUpBeforeClass(): void {}

    // -----------------------------------------------------------------------
    // start.php – formatUser() / formatTitle() / determineHashtag() logic
    // -----------------------------------------------------------------------

    public function testFormatUserNormalisesUnderscores(): void
    {
        $this->assertSame('John Doe', StartUtils::formatUser('John_Doe'));
    }

    public function testFormatUserMapsSpecialAlias(): void
    {
        $this->assertSame('Mr. Ibrahem', StartUtils::formatUser('Mr. Ibrahem 1'));
        $this->assertSame('Mr. Ibrahem', StartUtils::formatUser('Admin'));
    }

    public function testFormatUserPassesThroughRegularUser(): void
    {
        $this->assertSame('Regular User', StartUtils::formatUser('Regular_User'));
    }

    public function testFormatTitleNormalisesUnderscores(): void
    {
        $this->assertSame('My Article', StartUtils::formatTitle('My_Article'));
    }

    public function testFormatTitleFixesMrIbrahem1Prefix(): void
    {
        $result = StartUtils::formatTitle('Mr. Ibrahem 1/SomeArticle');
        $this->assertSame('Mr. Ibrahem/SomeArticle', $result);
    }

    public function testDetermineHashtagDefaultValue(): void
    {
        $hashtag = StartUtils::determineHashtag('SomeArticle', 'RegularUser');
        $this->assertSame('#mdwikicx', $hashtag);
    }

    public function testDetermineHashtagEmptyForMrIbrahemOwnPage(): void
    {
        $hashtag = StartUtils::determineHashtag('Mr. Ibrahem/SomePage', 'Mr. Ibrahem');
        $this->assertSame('', $hashtag);
    }

    public function testDetermineHashtagKeptWhenDifferentUser(): void
    {
        $hashtag = StartUtils::determineHashtag('Mr. Ibrahem/SomePage', 'OtherUser');
        $this->assertSame('#mdwikicx', $hashtag);
    }

    // -----------------------------------------------------------------------
    // make_summary()
    // -----------------------------------------------------------------------

    public function testMakeSummaryContainsRevid(): void
    {
        $summary = StartUtils::make_summary('98765', 'Paracetamol', 'fr', '#mdwikicx');
        $this->assertStringContainsString('98765', $summary);
    }

    public function testMakeSummaryContainsSourceTitle(): void
    {
        $summary = StartUtils::make_summary('111', 'Ibuprofen', 'de', '#mdwikicx');
        $this->assertStringContainsString('Ibuprofen', $summary);
    }

    public function testMakeSummaryContainsTargetLang(): void
    {
        $summary = StartUtils::make_summary('222', 'Aspirin', 'ja', '#mdwikicx');
        $this->assertStringContainsString('to:ja', $summary);
    }

    public function testMakeSummaryContainsHashtag(): void
    {
        $summary = StartUtils::make_summary('333', 'Title', 'es', '#mdwikicx');
        $this->assertStringContainsString('#mdwikicx', $summary);
    }


    public function testGetErrorsFileReturnsMainErrors(): void
    {
        $editResult = ['error' => 'protectedpage'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('protectedpage', $result);
    }

    public function testGetErrorsFileReturnsTitleBlacklistError(): void
    {
        $editResult = ['error' => 'titleblacklist violation'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('titleblacklist', $result);
    }

    public function testGetErrorsFileReturnsRateLimitedError(): void
    {
        $editResult = ['error' => 'ratelimited'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('ratelimited', $result);
    }

    public function testGetErrorsFileReturnsEditConflictError(): void
    {
        $editResult = ['error' => 'editconflict occurred'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('editconflict', $result);
    }

    public function testGetErrorsFileReturnsSpamFilterError(): void
    {
        $editResult = ['error' => 'spam filter triggered'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('spam filter', $result);
    }

    public function testGetErrorsFileReturnsAbusefilterError(): void
    {
        $editResult = ['error' => 'abusefilter-warning'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('abusefilter', $result);
    }

    public function testGetErrorsFileReturnsOAuthError(): void
    {
        $editResult = ['error' => 'mwoauth-invalid-authorization'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('mwoauth-invalid-authorization', $result);
    }

    public function testGetErrorsFileReturnsPlaceholderWhenNoMatch(): void
    {
        $editResult = ['error' => 'unknown_error'];
        $result = StartUtils::get_errors_file($editResult, 'errors');
        $this->assertEquals('errors', $result);
    }

    public function testGetErrorsFileReturnsWdErrorsWhenNoMatch(): void
    {
        $editResult = ['error' => 'unknown_error'];
        $result = StartUtils::get_errors_file($editResult, 'wd_errors');
        $this->assertEquals('wd_errors', $result);
    }

    public function testGetErrorsFileReturnsWdErrorsWhenProtectedpage(): void
    {
        $editResult = ['error' => 'protectedpage'];
        $result = StartUtils::get_errors_file($editResult, 'wd_errors');
        $this->assertEquals('wd_protectedpage', $result);
    }


    public function testGetErrorsFileReturnsPlaceholderForNoMatch(): void
    {
        $result = StartUtils::get_errors_file(['edit' => ['result' => 'Success']], 'errors');
        $this->assertSame('errors', $result);
    }

    public function testGetErrorsFileDetectsProtectedPage(): void
    {
        $result = StartUtils::get_errors_file(['error' => ['code' => 'protectedpage']], 'errors');
        $this->assertSame('protectedpage', $result);
    }

    public function testGetErrorsFileDetectsRateLimited(): void
    {
        $result = StartUtils::get_errors_file(['error' => ['code' => 'ratelimited']], 'errors');
        $this->assertSame('ratelimited', $result);
    }

    public function testGetErrorsFileDetectsAbuseFilter(): void
    {
        $result = StartUtils::get_errors_file(['error' => 'abusefilter triggered'], 'errors');
        $this->assertSame('abusefilter', $result);
    }

    public function testGetErrorsFileDetectsWdCsrftoken(): void
    {
        $result = StartUtils::get_errors_file(['error' => 'get_csrftoken failed'], 'wd_errors');
        $this->assertSame('wd_csrftoken', $result);
    }

    public function testGetErrorsFileDetectsWdUserPages(): void
    {
        $result = StartUtils::get_errors_file(['error' => 'Links to user pages is not allowed'], 'wd_errors');
        $this->assertSame('wd_user_pages', $result);
    }
}
