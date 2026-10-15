<?php

declare(strict_types=1);

namespace Tests\Process;

use PHPUnit\Framework\TestCase;
use Publish\Process\StartController;
use Publish\Process\ProcessEdit;
use Publish\Process\TextChanger;
use Publish\MdwikiSql\PublishReportsRepository;
use Publish\MdwikiSql\AccessHelps;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StartController::class)]
final class StartControllerTest extends TestCase
{
    public function testEnrichTabUsesWAllWordsWhenTrTypeIsNotLead(): void
    {
        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->method('getAccess')->willReturn([
            'access_key'    => 'key',
            'access_secret' => 'secret',
        ]);
        $mockAccessHelps->method('loadTitleData')->willReturn([
            'w_lead_words' => 50,
            'w_all_words'  => 200,
            'revid'        => 123,
        ]);

        $mockProcessEdit = $this->createMock(ProcessEdit::class);
        $mockProcessEdit->expects($this->once())
            ->method('handle')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(function (array $tab) {
                    return $tab['words'] === 200;
                }),
                $this->anything(),
            )
            ->willReturn(['result' => 'success']);

        $mockEditProcess = $this->createMock(TextChanger::class);
        $mockRepository = $this->createMock(PublishReportsRepository::class);

        $controller = new StartController(
            $mockProcessEdit,
            $mockEditProcess,
            $mockRepository,
            $mockAccessHelps
        );

        $request = [
            'tr_type'     => 'all',
            'title'       => 'Test_Title',
            'user'        => 'TestUser',
            'target'      => 'en',
            'sourcetitle' => 'Source_Title',
        ];

        $response = $controller->handle($request);
        $this->assertSame(['result' => 'success'], $response);
    }

    public function testEnrichTabUsesWLeadWordsWhenTrTypeIsLead(): void
    {
        $mockAccessHelps = $this->createMock(AccessHelps::class);
        $mockAccessHelps->method('getAccess')->willReturn([
            'access_key'    => 'key',
            'access_secret' => 'secret',
        ]);
        $mockAccessHelps->method('loadTitleData')->willReturn([
            'w_lead_words' => 50,
            'w_all_words'  => 200,
            'revid'        => 123,
        ]);

        $mockProcessEdit = $this->createMock(ProcessEdit::class);
        $mockProcessEdit->expects($this->once())
            ->method('handle')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(function (array $tab) {
                    return $tab['words'] === 50;
                }),
                $this->anything(),
            )
            ->willReturn(['result' => 'success']);

        $mockEditProcess = $this->createMock(TextChanger::class);
        $mockRepository = $this->createMock(PublishReportsRepository::class);

        $controller = new StartController(
            $mockProcessEdit,
            $mockEditProcess,
            $mockRepository,
            $mockAccessHelps
        );

        $request = [
            'tr_type'     => 'lead',
            'title'       => 'Test_Title',
            'user'        => 'TestUser',
            'target'      => 'en',
            'sourcetitle' => 'Source_Title',
        ];

        $response = $controller->handle($request);
        $this->assertSame(['result' => 'success'], $response);
    }
}
