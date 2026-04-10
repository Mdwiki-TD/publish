<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 * Tests for src/bots/process_edit.php  (namespace Publish\EditProcess)
 *            src/su/start.php             (helper functions)
 */
class StartTest extends TestCase
{
    public static function setUpBeforeClass(): void {}

    // -----------------------------------------------------------------------
    // start.php – formatUser() / formatTitle() / determineHashtag() logic
    // -----------------------------------------------------------------------

    public function testFormatUserNormalisesUnderscores(): void
    {
        $this->assertSame('John Doe', \Publish\Start\formatUser('John_Doe'));
    }

    public function testFormatUserMapsSpecialAlias(): void
    {
        $this->assertSame('Mr. Ibrahem', \Publish\Start\formatUser('Mr. Ibrahem 1'));
        $this->assertSame('Mr. Ibrahem', \Publish\Start\formatUser('Admin'));
    }

    public function testFormatUserPassesThroughRegularUser(): void
    {
        $this->assertSame('Regular User', \Publish\Start\formatUser('Regular_User'));
    }

    public function testFormatTitleNormalisesUnderscores(): void
    {
        $this->assertSame('My Article', \Publish\Start\formatTitle('My_Article'));
    }

    public function testFormatTitleFixesMrIbrahem1Prefix(): void
    {
        $result = \Publish\Start\formatTitle('Mr. Ibrahem 1/SomeArticle');
        $this->assertSame('Mr. Ibrahem/SomeArticle', $result);
    }

    public function testDetermineHashtagDefaultValue(): void
    {
        $hashtag = \Publish\Start\determineHashtag('SomeArticle', 'RegularUser');
        $this->assertSame('#mdwikicx', $hashtag);
    }

    public function testDetermineHashtagEmptyForMrIbrahemOwnPage(): void
    {
        $hashtag = \Publish\Start\determineHashtag('Mr. Ibrahem/SomePage', 'Mr. Ibrahem');
        $this->assertSame('', $hashtag);
    }

    public function testDetermineHashtagKeptWhenDifferentUser(): void
    {
        $hashtag = \Publish\Start\determineHashtag('Mr. Ibrahem/SomePage', 'OtherUser');
        $this->assertSame('#mdwikicx', $hashtag);
    }

    // -----------------------------------------------------------------------
    // make_summary()
    // -----------------------------------------------------------------------

    public function testMakeSummaryContainsRevid(): void
    {
        $summary = \Publish\Start\make_summary('98765', 'Paracetamol', 'fr', '#mdwikicx');
        $this->assertStringContainsString('98765', $summary);
    }

    public function testMakeSummaryContainsSourceTitle(): void
    {
        $summary = \Publish\Start\make_summary('111', 'Ibuprofen', 'de', '#mdwikicx');
        $this->assertStringContainsString('Ibuprofen', $summary);
    }

    public function testMakeSummaryContainsTargetLang(): void
    {
        $summary = \Publish\Start\make_summary('222', 'Aspirin', 'ja', '#mdwikicx');
        $this->assertStringContainsString('to:ja', $summary);
    }

    public function testMakeSummaryContainsHashtag(): void
    {
        $summary = \Publish\Start\make_summary('333', 'Title', 'es', '#mdwikicx');
        $this->assertStringContainsString('#mdwikicx', $summary);
    }
}
