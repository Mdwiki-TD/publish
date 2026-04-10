<?php

namespace Tests\Bots\Unit;

use PHPUnit\Framework\TestCase;

class WdUnitTest extends TestCase
{
    // Helper: Creates a callable that returns a constant value
    private function returns(mixed $value): callable
    {
        return fn() => $value;
    }

    // Helper: Creates a callable that captures arguments and returns a value
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
    // getAccessCredentials
    // -----------------------------------------------------------------------

    public function testGetAccessCredentialsReturnsSuppliedKeysDirectly(): void
    {
        $result = \Publish\WD\getAccessCredentials(
            'user',
            'my_key',
            'my_secret',
            $this->noop(),
            $this->noop(),
            $this->noop()
        );

        $this->assertSame(['my_key', 'my_secret'], $result);
    }

    public function testGetAccessCredentialsReturnsTwoElements(): void
    {
        $result = \Publish\WD\getAccessCredentials(
            'user',
            'k',
            's',
            $this->noop(),
            $this->noop(),
            $this->noop()
        );

        $this->assertCount(2, $result);
    }

    public function testGetAccessCredentialsFallsBackToNewHelper(): void
    {
        $dbNew = $this->returns(['access_key' => 'new_k', 'access_secret' => 'new_s']);

        $result = \Publish\WD\getAccessCredentials(
            'alice',
            '',
            '',
            $dbNew,
            $this->noop(),
            $this->noop()
        );

        $this->assertSame(['new_k', 'new_s'], $result);
    }

    public function testGetAccessCredentialsFallsBackToOldHelperWhenNewEmpty(): void
    {
        $dbOld = $this->returns(['access_key' => 'old_k', 'access_secret' => 'old_s']);

        $result = \Publish\WD\getAccessCredentials(
            'bob',
            null,
            null,
            $this->returns([]),
            $dbOld,
            $this->noop()
        );

        $this->assertSame(['old_k', 'old_s'], $result);
    }

    public function testGetAccessCredentialsReturnsNullWhenBothHelpersEmpty(): void
    {
        $result = \Publish\WD\getAccessCredentials(
            'ghost',
            '',
            '',
            $this->returns([]),
            $this->returns([]),
            $this->noop()
        );

        $this->assertNull($result);
    }

    public function testGetAccessCredentialsFallsBackWhenOnlySecretMissing(): void
    {
        $dbNew = $this->returns(['access_key' => 'db_k', 'access_secret' => 'db_s']);

        $result = \Publish\WD\getAccessCredentials(
            'user',
            'has_key',
            '',
            $dbNew,
            $this->noop(),
            $this->noop()
        );

        $this->assertSame(['db_k', 'db_s'], $result);
    }

    // -----------------------------------------------------------------------
    // LinkToWikidata
    // -----------------------------------------------------------------------

    public function testLinkToWikidataReturnsSuccessOnApiSuccess(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'Paracetamol',
            'fr',
            'user',
            'Paracétamol',
            '',
            '',
            $this->returns([['qid' => 'Q999']]),                           // getQid
            $this->returns(['key', 'secret']),                            // getCreds
            $this->returns(['success' => 1, 'pageinfo' => []]),           // linkIt
            $this->noop()
        );

        $this->assertSame('success', $result['result']);
        $this->assertSame('Q999', $result['qid']);
    }

    public function testLinkToWikidataPassesThroughApiError(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'Paracetamol',
            'fr',
            'user',
            'Paracétamol',
            '',
            '',
            $this->returns([['qid' => 'Q888']]),
            $this->returns(['key', 'secret']),
            $this->returns(['error' => ['code' => 'protectedpage']]),
            $this->noop()
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Q888', $result['qid']);
    }

    public function testLinkToWikidataReturnsErrorWhenCredentialsMissing(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'Title',
            'es',
            'ghost_user',
            'Título',
            '',
            '',
            $this->returns([]),
            $this->returns(null),   // getCreds → null
            $this->noop(),
            $this->noop()
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('ghost_user', $result['error']);
    }

    public function testLinkToWikidataPassesQidToLinkIt(): void
    {
        $capturedArgs = null;

        \Publish\WD\LinkToWikidata(
            'Douglas Adams',
            'de',
            'user',
            'Douglas Adams (de)',
            '',
            '',
            $this->returns([['qid' => 'Q42']]),
            $this->returns(['k', 's']),
            $this->capture($capturedArgs, ['success' => 1]),
            $this->noop()
        );

        // $capturedArgs[0] = $qid
        $this->assertSame('Q42', $capturedArgs[0]);
    }

    public function testLinkToWikidataUsesEmptyQidWhenNotInDb(): void
    {
        $capturedArgs = null;

        \Publish\WD\LinkToWikidata(
            'UnknownTitle',
            'ja',
            'user',
            '未知',
            '',
            '',
            $this->returns([]),        // No QID in DB
            $this->returns(['k', 's']),
            $this->capture($capturedArgs, ['success' => 1]),
            $this->noop()
        );

        $this->assertSame('', $capturedArgs[0]); // empty qid
    }

    public function testLinkToWikidataAlwaysAttachesQidToResult(): void
    {
        $result = \Publish\WD\LinkToWikidata(
            'Title',
            'ar',
            'user',
            'title',
            '',
            '',
            $this->returns([['qid' => 'Q77']]),
            $this->returns(['k', 's']),
            $this->returns(['error' => ['code' => 'badtoken']]),
            $this->noop()
        );

        $this->assertSame('Q77', $result['qid']);
    }
}
