<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

use function Publish\Process\ProcessEdit\prepareApiParams;

class EditProcessTest extends TestCase
{
    public static function setUpBeforeClass(): void {}

    public function testPrepareApiParamsReturnsCorrectStructure(): void
    {
        $title = 'Test Page';
        $summary = 'Test summary';
        $text = 'Test content';
        $request = [];

        $result = prepareApiParams($title, $summary, $text, $request);

        $this->assertEquals('edit', $result['action']);
        $this->assertEquals($title, $result['title']);
        $this->assertEquals($summary, $result['summary']);
        $this->assertEquals($text, $result['text']);
        $this->assertEquals('json', $result['format']);
    }

    public function testPrepareApiParamsIncludesCaptchaFields(): void
    {
        $request = [
            'wpCaptchaId' => '12345',
            'wpCaptchaWord' => 'answer'
        ];

        $result = prepareApiParams('Test', 'Summary', 'Content', $request);

        $this->assertEquals('12345', $result['wpCaptchaId']);
        $this->assertEquals('answer', $result['wpCaptchaWord']);
    }

    public function testPrepareApiParamsBasicFields(): void
    {
        $params = prepareApiParams('MyTitle', 'My summary', 'Article body', []);
        $this->assertSame('edit', $params['action']);
        $this->assertSame('MyTitle', $params['title']);
        $this->assertSame('json', $params['format']);
        $this->assertArrayNotHasKey('wpCaptchaId', $params);
    }

    public function testPrepareApiParamsIncludesCaptchaWhenPresent(): void
    {
        $request = ['wpCaptchaId' => 'abc123', 'wpCaptchaWord' => 'xkcd'];
        $params  = prepareApiParams('T', 'S', 'B', $request);
        $this->assertSame('abc123', $params['wpCaptchaId']);
        $this->assertSame('xkcd', $params['wpCaptchaWord']);
    }

    public function testPrepareApiParamsOmitsCaptchaWhenPartiallyPresent(): void
    {
        $params = prepareApiParams('T', 'S', 'B', ['wpCaptchaId' => 'only-id']);
        $this->assertArrayNotHasKey('wpCaptchaId', $params);
        $this->assertArrayNotHasKey('wpCaptchaWord', $params);
    }
}
