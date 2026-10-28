<?php

declare(strict_types=1);

namespace Tests\Publish\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Publish\Api\WdApi;
use Publish\Api\WikiApi;
use Publish\MdwikiSql\AccessHelps;
use Publish\MdwikiSql\PublishReportsRepository;
use Publish\MediaWikiClient\MediaWikiEditClient;
use Publish\Process\EditProcessLog;
use Publish\Process\ProcessEdit;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ProcessEdit::class)]
final class ProcessEditTest extends TestCase
{
    public function testPrepareApiParamsIncludesCaptchaWhenPresent(): void
    {
        $params = ProcessEdit::prepareApiParams(
            'Test Title',
            'Summary',
            'Text content',
            ['wpCaptchaId' => 'c123', 'wpCaptchaWord' => 'w456']
        );

        $this->assertSame('Test Title', $params['title']);
        $this->assertSame('c123', $params['wpCaptchaId']);
        $this->assertSame('w456', $params['wpCaptchaWord']);
    }

    public function testShouldAddedToWikidataReturnsFalseForUserNamespace(): void
    {
        $mockWikiApi = $this->createMock(WikiApi::class);
        $mockWikiApi->method('GetTitleInfo')
            // ->with('User:TestUser/Page', 'en')
            ->willReturn(['ns' => 2, 'title' => 'User:TestUser/Page']);

        $processEdit = new ProcessEdit(wikiApi: $mockWikiApi);
        $this->assertFalse($processEdit->shouldAddedToWikidata('en', 'User:TestUser/Page'));
    }

    public function testShouldAddedToWikidataReturnsTrueForMainNamespace(): void
    {
        $mockWikiApi = $this->createMock(WikiApi::class);
        $mockWikiApi->method('GetTitleInfo')
            // ->with('Main_Article', 'en')
            ->willReturn(['ns' => 0, 'title' => 'Main_Article']);

        $processEdit = new ProcessEdit(wikiApi: $mockWikiApi);
        $this->assertTrue($processEdit->shouldAddedToWikidata('en', 'Main_Article'));
    }

    public function testRetryWithFallbackUserSuccess(): void
    {
        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->method('getAccess')
            // ->with('Mr. Ibrahem')
            ->willReturn(['access_key' => 'fb_key', 'access_secret' => 'fb_secret']);

        $mockWdApi = $this->createMock(WdApi::class);
        $mockWdApi->method('LinkToWikidata')
            ->willReturn(['qid' => 'Q12345']);

        $processEdit = new ProcessEdit(
            wdApi: $mockWdApi,
            accessHelps: $mockAccessHelps
        );

        $result = $processEdit->retryWithFallbackUser('SourceTitle', 'en', 'TargetTitle', 'OriginalUser');

        $this->assertSame('Q12345', $result['qid']);
        $this->assertSame('Mr. Ibrahem', $result['fallback_user']);
        $this->assertSame('OriginalUser', $result['original_user']);
    }

    public function testHandleSuccessfulEditSkipForUserNamespace(): void
    {
        $mockWikiApi = $this->createMock(WikiApi::class);
        $mockWikiApi->method('GetTitleInfo')->willReturn(['ns' => 2]);

        $processEdit = new ProcessEdit(wikiApi: $mockWikiApi);
        $result = $processEdit->handleSuccessfulEdit('Source', 'en', 'User', 'User:User/Page', [], 'rand1');

        $this->assertSame(['error' => 'skip link to wd for user pages'], $result);
    }

    public function testHandleSuccessfulEditTriggersFallbackOnCsrfFailure(): void
    {
        $mockWikiApi = $this->createMock(WikiApi::class);
        $mockWikiApi->method('GetTitleInfo')->willReturn(['ns' => 0]);

        $mockWdApi = $this->createMock(WdApi::class);
        $mockWdApi->expects($this->exactly(2))
            ->method('LinkToWikidata')
            ->willReturnOnConsecutiveCalls(
                ['error' => 'get_csrf_token failed'],
                ['qid' => 'Q789']
            );

        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->method('getAccess')
            // ->with('Mr. Ibrahem')
            ->willReturn(['access_key' => 'k', 'access_secret' => 's']);

        $mockRepo = $this->createMock(PublishReportsRepository::class);
        $mockRepo->expects($this->once())->method('insertPublishReports');

        $processEdit = new ProcessEdit(
            wdApi: $mockWdApi,
            accessHelps: $mockAccessHelps,
            repository: $mockRepo,
            wikiApi: $mockWikiApi
        );

        $access = ['access_key' => 'orig_key', 'access_secret' => 'orig_secret'];
        $result = $processEdit->handleSuccessfulEdit('Source', 'en', 'OtherUser', 'Target', $access, 'rand123');

        $this->assertSame('get_csrf_token failed', $result['error']);
        $this->assertArrayHasKey('fallback', $result);
    }

    public function testHandleFullFlowSuccessfulEdit(): void
    {
        $mockWikiApi = $this->createMock(WikiApi::class);
        $mockWikiApi->method('GetTitleInfo')->willReturn(['ns' => 0]);

        $mockEditClient = $this->createMock(MediaWikiEditClient::class);
        $mockEditClient->expects($this->once())
            ->method('publishEdit')
            ->willReturn(['edit' => ['result' => 'Success']]);

        $mockWdApi = $this->createMock(WdApi::class);
        $mockWdApi->method('LinkToWikidata')->willReturn(['qid' => 'Q999']);

        $mockLog = $this->createMock(EditProcessLog::class);
        $mockLog->expects($this->once())
            ->method('addToDb')
            ->willReturn(['execute_query' => true]);

        $mockRepo = $this->createMock(PublishReportsRepository::class);

        $processEdit = new ProcessEdit(
            editProcessLog: $mockLog,
            wdApi: $mockWdApi,
            editClient: $mockEditClient,
            repository: $mockRepo,
            wikiApi: $mockWikiApi
        );

        $request = [
            'user'    => 'TestUser',
            'tr_type' => 'all',
        ];
        $access = ['access_key' => 'key', 'access_secret' => 'secret'];
        $tab = [
            'title'       => 'Target Title',
            'sourcetitle' => 'Source Title',
            'summary'     => 'Summary',
            'lang'        => 'en',
            'campaign'    => 'medwiki',
            'revid'       => 100,
            'words'       => 500,
        ];

        $res = $processEdit->handle(
            $request,
            $access,
            'Wikitext',
            $tab,
            'rand456',
        );
        $this->assertSame('Success', $res['edit']['result']);
    }
}
