<?php

namespace Tests\Bots;

use PHPUnit\Framework\TestCase;

/**
 *  src/bots/revids_bot.php   (namespace Publish\Revids)
 */
class RevidsTest extends TestCase
{
    public function testGetRevidReturnsCorrectRevid(): void
    {
        $tmpFile = sys_get_temp_dir() . '/all_pages_revids_test.json';
        file_put_contents($tmpFile, json_encode([
            'Paracetamol'    => '12345',
            'Ibuprofen'      => '67890',
        ]));
        putenv('ALL_PAGES_REVIDS_PATH=' . $tmpFile);

        $this->assertSame('12345', \Publish\Revids\get_revid('Paracetamol'));
        $this->assertSame('67890', \Publish\Revids\get_revid('Ibuprofen'));
    }

    public function testGetRevidReturnsEmptyForMissingTitle(): void
    {
        $tmpFile = sys_get_temp_dir() . '/all_pages_revids_test.json';
        file_put_contents($tmpFile, json_encode(['KnownTitle' => '99999']));
        putenv('ALL_PAGES_REVIDS_PATH=' . $tmpFile);

        $result = \Publish\Revids\get_revid('UnknownTitle');
        $this->assertSame('', $result);
    }

    public function testGetRevidHandlesMalformedJson(): void
    {
        $tmpFile = sys_get_temp_dir() . '/all_pages_revids_test.json';
        file_put_contents($tmpFile, 'NOT_VALID_JSON{{{{');
        putenv('ALL_PAGES_REVIDS_PATH=' . $tmpFile);

        $result = \Publish\Revids\get_revid('AnyTitle');
        $this->assertSame('', $result);
    }

}
