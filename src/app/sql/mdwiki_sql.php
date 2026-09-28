<?php

namespace Publish\MdwikiSql;

use Publish\MdwikiSql\Database;

function execute_query(string $sqlQuery, ?array $params = null): bool
{
    // Create a new database object
    $db = new Database('DB_NAME');

    // Execute a SQL query
    $results = $db->executequery($sqlQuery, $params);

    // Print the results
    // foreach ($results as $row) echo $row['column1'] . " " . $row['column2'] . "<br>";

    // Destroy the database object
    $db = null;
    return $results;
};
function fetch_query(string $sqlQuery, ?array $params = null): array
{
    // Create a new database object
    $db = new Database('DB_NAME');

    // Execute a SQL query
    $results = $db->fetchquery($sqlQuery, $params);

    // Print the results
    // foreach ($results as $row) echo $row['column1'] . " " . $row['column2'] . "<br>";

    // Destroy the database object
    $db = null;
    return $results;
};
