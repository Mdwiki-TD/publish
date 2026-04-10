<?php

namespace Tests\Bots\Unit;

use PHPUnit\Framework\TestCase;

class WikiApiUnitTest extends TestCase
{
    private function returns(mixed $value): callable
    {
        return fn() => $value;
    }

    private function capture(mixed &$captured, mixed $returnValue = null): callable
    {
        return function () use (&$captured, $returnValue) {
            $captured = func_get_args();
            return $returnValue;
        };
    }

    private function noop(): callable
    {
        return fn() => null;
    }

    // -----------------------------------------------------------------------
    // GetTitleInfo
    // -----------------------------------------------------------------------

    public function testGetTitleInfoReturnsFirstPage(): void
    {
        $page    = ['pageid' => 15580374, 'ns' => 0, 'title' => 'Main Page'];
        $apiJson = json_encode(['query' => ['pages' => [$page]]]);

        $result = \Publish\WikiApi\GetTitleInfo('Main Page', 'en', $this->returns($apiJson), $this->noop());

        $this->assertSame($page, $result);
    }

    public function testGetTitleInfoBuildsUrlWithCorrectLang(): void
    {
        $capturedArgs = null;
        $apiJson      = json_encode(['query' => ['pages' => [['pageid' => 1, 'ns' => 0, 'title' => 'T']]]]);

        \Publish\WikiApi\GetTitleInfo('SomeTitle', 'fr', $this->capture($capturedArgs, $apiJson), $this->noop());

        $this->assertStringContainsString('fr.wikipedia.org', $capturedArgs[0]);
    }

    public function testGetTitleInfoUsesRfc3986Encoding(): void
    {
        $capturedArgs = null;
        $apiJson      = json_encode(['query' => ['pages' => [['pageid' => 1, 'ns' => 0, 'title' => 'T']]]]);

        \Publish\WikiApi\GetTitleInfo('Article With Spaces', 'en', $this->capture($capturedArgs, $apiJson), $this->noop());

        $this->assertStringContainsString('%20', $capturedArgs[0]);
        $this->assertStringNotContainsString('Article+With', $capturedArgs[0]);
    }

    public function testGetTitleInfoReturnsNullOnException(): void
    {
        $throwingCurl = function () {
            throw new \Exception('timeout');
        };

        $result = \Publish\WikiApi\GetTitleInfo('Page', 'en', $throwingCurl, $this->noop());

        $this->assertNull($result);
    }

    public function testGetTitleInfoHandlesUserPageMg(): void
    {
        $title   = "Mpikambana:Doc James/Fahaverezan'ny volo";
        $page    = ['pageid' => 298895, 'ns' => 2, 'title' => $title];
        $apiJson = json_encode(['query' => ['pages' => [$page]]]);

        $result = \Publish\WikiApi\GetTitleInfo($title, 'mg', $this->returns($apiJson), $this->noop());

        $this->assertSame($page, $result);
    }
}
