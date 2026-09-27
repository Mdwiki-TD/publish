<?php

namespace Publish\MdwikiSql;

use Publish\MdwikiSql\Database;

function execute_query(string $sqlQuery, $params = null, $tableName = null)
{

    // Create a new database object
    $db = new Database('DB_NAME');

    // Execute a SQL query
    if ($params) {
        $results = $db->executequery($sqlQuery, $params);
    } else {
        $results = $db->executequery($sqlQuery);
    }

    // Print the results
    // foreach ($results as $row) echo $row['column1'] . " " . $row['column2'] . "<br>";

    // Destroy the database object
    $db = null;
    return $results;
};

function fetch_query(string $sqlQuery, ?array $params = null, $tableName = null): array
{

    // Create a new database object
    $db = new Database('DB_NAME');

    // Execute a SQL query
    if ($params) {
        $results = $db->fetchquery($sqlQuery, $params);
    } else {
        $results = $db->fetchquery($sqlQuery);
    }

    // Print the results
    // foreach ($results as $row) echo $row['column1'] . " " . $row['column2'] . "<br>";

    // Destroy the database object
    $db = null;
    return $results;
};
