<?php

/**
 * Tests for: TextChanger
 * Source: src/app/Process/TextChanger.php
 *
 * The fix function is injected as a closure, so these tests never touch the
 * real \WpRefs\FixPage\fix_page_with_setting() even if it is autoloaded.
 */

declare(strict_types=1);

namespace Tests\Publish\Process;

use Publish\MdwikiSql\Database;
use Publish\Process\TextChanger;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TextChanger::class)]
class TextChangerTest extends TestCase
{
    /** @var array<int, array<int, mixed>> */
    private array $calls = [];

    protected function setUp(): void
    {
        $this->calls = [];
    }

    /**
     * Recording fixer: stores its arguments and returns "FIXED:<text>".
     */
    private function recordingFixer(): callable
    {
        return function (...$args): string {
            $this->calls[] = $args;

            return 'FIXED:' . $args[2];
        };
    }

    /**
     * @param array<int, mixed> $rows What Database::fetchQuery should return.
     */
    private function makeChanger(array $rows): TextChanger
    {
        $db = $this->createStub(Database::class);
        $db->method('fetchQuery')->willReturn($rows);

        return new TextChanger($db, $this->recordingFixer());
    }

    public function testReturnsOriginalTextWhenNoFixerIsAvailable(): void
    {
        $db = $this->createMock(Database::class);
        // Settings must not even be fetched when there is no fixer.
        $db->expects($this->never())->method('fetchQuery');

        $changer = new class ($db) extends TextChanger {
            protected function resolveFixer(): ?callable
            {
                return null;
            }
        };

        $this->assertSame(
            'original text',
            $changer->text_changes('Source', 'Title', 'original text', 'ar', 123)
        );
    }

    public function testReturnsResultOfFixer(): void
    {
        $this->assertSame(
            'FIXED:some wikitext',
            $this->makeChanger([])->text_changes('Source', 'Title', 'some wikitext', 'fr', 42)
        );
    }

    public function testQueriesLanguageSettingsWithLangCode(): void
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchQuery')
            ->with(
                $this->stringContains('language_settings'),
                ['sv']
            )
            ->willReturn([]);

        (new TextChanger($db, $this->recordingFixer()))
            ->text_changes('Source', 'Title', 'text', 'sv', 1);
    }

    public function testPassesAllArgumentsAndCastSettingsToFixer(): void
    {
        $changer = $this->makeChanger([
            ['move_dots' => 1, 'expend' => 0, 'add_en_lang' => '1'],
        ]);

        $changer->text_changes('Source Title', 'Target Title', 'body', 'es', 'rev-99');

        $this->assertCount(1, $this->calls);
        $this->assertSame(
            ['Source Title', 'Target Title', 'body', 'es', 'rev-99', true, false, true],
            $this->calls[0]
        );
    }

    public function testIntegerRevisionIdIsForwarded(): void
    {
        $this->makeChanger([])->text_changes('S', 'T', 'x', 'de', 777);

        $this->assertSame(777, $this->calls[0][4]);
    }

    public function testSettingsAreNullWhenNoRowIsFound(): void
    {
        $this->makeChanger([])->text_changes('S', 'T', 'x', 'it', 1);

        $this->assertNull($this->calls[0][5]);
        $this->assertNull($this->calls[0][6]);
        $this->assertNull($this->calls[0][7]);
    }

    public function testSettingsAreNullWhenFirstRowIsNotAnArray(): void
    {
        $this->makeChanger(['not-an-array'])->text_changes('S', 'T', 'x', 'it', 1);

        $this->assertNull($this->calls[0][5]);
        $this->assertNull($this->calls[0][6]);
        $this->assertNull($this->calls[0][7]);
    }

    public function testPartialRowOnlyCastsPresentKeys(): void
    {
        $this->makeChanger([['expend' => 1]])->text_changes('S', 'T', 'x', 'nl', 1);

        $this->assertNull($this->calls[0][5], 'move_dots missing -> null');
        $this->assertTrue($this->calls[0][6], 'expend present -> true');
        $this->assertNull($this->calls[0][7], 'add_en_lang missing -> null');
    }

    public function testNullColumnValuesStayNull(): void
    {
        // isset() is false for null, so NULL columns must stay null (not false).
        $this->makeChanger([
            ['move_dots' => null, 'expend' => null, 'add_en_lang' => null],
        ])->text_changes('S', 'T', 'x', 'pt', 1);

        $this->assertNull($this->calls[0][5]);
        $this->assertNull($this->calls[0][6]);
        $this->assertNull($this->calls[0][7]);
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function boolCastProvider(): array
    {
        return [
            'int 1'        => [1, true],
            'int 0'        => [0, false],
            'string "1"'   => ['1', true],
            'string "0"'   => ['0', false],
            'empty string' => ['', false],
            'bool true'    => [true, true],
            'bool false'   => [false, false],
        ];
    }

    #[DataProvider('boolCastProvider')]
    public function testMoveDotsIsCastToBool(mixed $dbValue, bool $expected): void
    {
        $this->makeChanger([['move_dots' => $dbValue]])
            ->text_changes('S', 'T', 'x', 'ru', 1);

        $this->assertSame($expected, $this->calls[0][5]);
    }
}
