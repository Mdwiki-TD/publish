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
}
