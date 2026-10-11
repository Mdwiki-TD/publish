<?php

namespace Publish\MdwikiSql;

interface DatabaseInterface
{

    public function disableFullGroupByMode(string $sqlQuery): void;

    public function fetchQuery(string $sqlQuery, ?array $params = null): array;
    public function executeQuery(string $sqlQuery, ?array $params = null): bool;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;

    public function inTransaction(): bool;

    public function __destruct();
}
