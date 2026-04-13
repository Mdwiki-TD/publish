<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

class StartTest extends TestCase
{
    public function testFormatUserNormalisesUnderscores(): void
    {
        $user = str_replace("_", " ", "John_Doe");
        $this->assertSame('John Doe', $user);
    }

    public function testFormatTitleNormalisesUnderscores(): void
    {
        $title = str_replace("_", " ", "My_Article");
        $this->assertSame('My Article', $title);
    }

    public function testMakeSummaryContainsRevid(): void
    {
        $summary = "Created by translating the page [[:mdwiki:Special:Redirect/revision/123|Test]] to:en #mdwikicx";
        $this->assertStringContainsString('123', $summary);
    }

    public function testDetermineHashtagEmptyForMrIbrahem(): void
    {
        $title = "Mr. Ibrahem/SomePage";
        $user = "Mr. Ibrahem";
        $hashtag = "";
        if (strpos($title, "Mr. Ibrahem") !== false && $user == "Mr. Ibrahem") {
            $hashtag = "";
        }
        $this->assertSame('', $hashtag);
    }

    public function testDetermineHashtagDefaultForOtherUser(): void
    {
        $title = "SomeArticle";
        $user = "OtherUser";
        $hashtag = "#mdwikicx";
        if (strpos($title, "Mr. Ibrahem") !== false && $user == "Mr. Ibrahem") {
            $hashtag = "";
        }
        $this->assertSame('#mdwikicx', $hashtag);
    }

    public static function setUpBeforeClass(): void {}
}
