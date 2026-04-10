<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

final class ProcessEditTest extends TestCase
{
    public function testGetErrorsFileReturnsMainErrors(): void
    {
        $editResult = ['error' => 'protectedpage'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('protectedpage', $result);
    }

    public function testGetErrorsFileReturnsTitleBlacklistError(): void
    {
        $editResult = ['error' => 'titleblacklist violation'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('titleblacklist', $result);
    }

    public function testGetErrorsFileReturnsRateLimitedError(): void
    {
        $editResult = ['error' => 'ratelimited'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('ratelimited', $result);
    }

    public function testGetErrorsFileReturnsEditConflictError(): void
    {
        $editResult = ['error' => 'editconflict occurred'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('editconflict', $result);
    }

    public function testGetErrorsFileReturnsSpamFilterError(): void
    {
        $editResult = ['error' => 'spam filter triggered'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('spam filter', $result);
    }

    public function testGetErrorsFileReturnsAbusefilterError(): void
    {
        $editResult = ['error' => 'abusefilter-warning'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('abusefilter', $result);
    }

    public function testGetErrorsFileReturnsOAuthError(): void
    {
        $editResult = ['error' => 'mwoauth-invalid-authorization'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('mwoauth-invalid-authorization', $result);
    }

    public function testGetErrorsFileReturnsPlaceholderWhenNoMatch(): void
    {
        $editResult = ['error' => 'unknown_error'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'errors');
        $this->assertEquals('errors', $result);
    }

    public function testGetErrorsFileReturnsWdErrorsWhenNoMatch(): void
    {
        $editResult = ['error' => 'unknown_error'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'wd_errors');
        $this->assertEquals('wd_errors', $result);
    }

    public function testGetErrorsFileReturnsWdErrorsWhenProtectedpage(): void
    {
        $editResult = ['error' => 'protectedpage'];
        $result = \Publish\EditProcess\get_errors_file($editResult, 'wd_errors');
        $this->assertEquals('wd_errors', $result);
    }


    public function testGetErrorsFileReturnsPlaceholderForNoMatch(): void
    {
        $result = \Publish\EditProcess\get_errors_file(['edit' => ['result' => 'Success']], 'errors');
        $this->assertSame('errors', $result);
    }

    public function testGetErrorsFileDetectsProtectedPage(): void
    {
        $result = \Publish\EditProcess\get_errors_file(['error' => ['code' => 'protectedpage']], 'errors');
        $this->assertSame('protectedpage', $result);
    }

    public function testGetErrorsFileDetectsRateLimited(): void
    {
        $result = \Publish\EditProcess\get_errors_file(['error' => ['code' => 'ratelimited']], 'errors');
        $this->assertSame('ratelimited', $result);
    }

    public function testGetErrorsFileDetectsAbuseFilter(): void
    {
        $result = \Publish\EditProcess\get_errors_file(['error' => 'abusefilter triggered'], 'errors');
        $this->assertSame('abusefilter', $result);
    }

    public function testGetErrorsFileDetectsWdCsrftoken(): void
    {
        $result = \Publish\EditProcess\get_errors_file(['error' => 'get_csrftoken failed'], 'wd_errors');
        $this->assertSame('wd_csrftoken', $result);
    }

    public function testGetErrorsFileDetectsWdUserPages(): void
    {
        $result = \Publish\EditProcess\get_errors_file(['error' => 'Links to user pages is not allowed'], 'wd_errors');
        $this->assertSame('wd_user_pages', $result);
    }


    public function testPrepareApiParamsReturnsCorrectStructure(): void
    {
        $title = 'Test Page';
        $summary = 'Test summary';
        $text = 'Test content';
        $request = [];

        $result = \Publish\EditProcess\prepareApiParams($title, $summary, $text, $request);

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

        $result = \Publish\EditProcess\prepareApiParams('Test', 'Summary', 'Content', $request);

        $this->assertEquals('12345', $result['wpCaptchaId']);
        $this->assertEquals('answer', $result['wpCaptchaWord']);
    }

    public function testPrepareApiParamsBasicFields(): void
    {
        $params = \Publish\EditProcess\prepareApiParams('MyTitle', 'My summary', 'Article body', []);
        $this->assertSame('edit', $params['action']);
        $this->assertSame('MyTitle', $params['title']);
        $this->assertSame('json', $params['format']);
        $this->assertArrayNotHasKey('wpCaptchaId', $params);
    }

    public function testPrepareApiParamsIncludesCaptchaWhenPresent(): void
    {
        $request = ['wpCaptchaId' => 'abc123', 'wpCaptchaWord' => 'xkcd'];
        $params  = \Publish\EditProcess\prepareApiParams('T', 'S', 'B', $request);
        $this->assertSame('abc123', $params['wpCaptchaId']);
        $this->assertSame('xkcd', $params['wpCaptchaWord']);
    }

    public function testPrepareApiParamsOmitsCaptchaWhenPartiallyPresent(): void
    {
        $params = \Publish\EditProcess\prepareApiParams('T', 'S', 'B', ['wpCaptchaId' => 'only-id']);
        $this->assertArrayNotHasKey('wpCaptchaId', $params);
        $this->assertArrayNotHasKey('wpCaptchaWord', $params);
    }
}
