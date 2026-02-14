<?php

declare(strict_types=1);

/**
 * Database abstraction layer for MDWiki publishing system.
 *
 * This file provides PDO-based database connectivity with support for
 * both local development and Toolforge production environments.
 *
 * @package Publish\MdwikiSql
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see https://www.php.net/manual/en/book.pdo.php
 *
 * @example
 * // Fetch rows from database
 * use function Publish\MdwikiSql\fetch_query;
 * $results = fetch_query("SELECT * FROM pages WHERE lang = ?", ['en']);
 *
 * // Execute INSERT/UPDATE/DELETE
 * use function Publish\MdwikiSql\execute_query;
 * execute_query("INSERT INTO pages (title) VALUES (?)", ['Test']);
 */

namespace Publish\MdwikiSql;

use PDO;
use PDOException;
use function Publish\Helps\pub_test_print;

/**
 * Database connection and query execution handler.
 *
 * Manages PDO connections with automatic configuration based on
 * server environment (localhost vs Toolforge).
 *
 * @package Publish\MdwikiSql
 */
class Database
{
    /**
     * PDO database connection instance.
     *
     * @var PDO|null
     */
    private ?PDO $db = null;

    /**
     * Database host address.
     *
     * @var string
     */
    private string $host;

    /**
     * Home directory path for configuration files.
     *
     * @var string
     */
    private string $home_dir;

    /**
     * Database username.
     *
     * @var string
     */
    private string $user;

    /**
     * Database password.
     *
     * @var string
     */
    private string $password;

    /**
     * Database name.
     *
     * @var string
     */
    private string $dbname;

    /**
     * Database suffix for multi-database support.
     *
     * @var string
     */
    private string $db_suffix;

    /**
     * Flag indicating if ONLY_FULL_GROUP_BY mode has been disabled.
     *
     * @var bool
     */
    private bool $groupByModeDisabled = false;

    /**
     * Initializes database connection based on server environment.
     *
     * Automatically detects localhost vs production and configures
     * connection parameters accordingly.
     *
     * @param string $server_name Server hostname, used to detect environment
     * @param string $db_suffix   Database suffix (default: 'mdwiki')
     *
     * @throws PDOException If connection fails (handled internally with user message)
     *
     * @example
     * $db = new Database('localhost', 'mdwiki');
     * $results = $db->fetchquery('SELECT * FROM pages');
     */
    public function __construct(string $server_name, string $db_suffix = 'mdwiki')
    {
        if (empty($db_suffix)) {
            $db_suffix = 'mdwiki';
        }

        $this->home_dir = getenv("HOME") ?: 'I:/mdwiki/mdwiki';
        $this->db_suffix = $db_suffix;
        $this->set_db($server_name);
    }

    /**
     * Configures and establishes database connection.
     *
     * Loads credentials from INI file and establishes PDO connection.
     * Different configurations are used for localhost vs production.
     *
     * SECURITY NOTE: Localhost password should be moved to environment variable.
     * @see ANALYSIS_REPORT.md SEC-001
     *
     * @param string $server_name Server hostname to determine environment
     *
     * @return void
     */
    private function set_db(string $server_name): void
    {
        $ts_mycnf = parse_ini_file($this->home_dir . "/confs/db.ini");

        if ($server_name === 'localhost') {
            $this->host = 'localhost:3306';
            $this->dbname = $ts_mycnf['user'] . "__" . $this->db_suffix;
            $this->user = 'root';
            // SECURITY ISSUE: Hardcoded password - should use environment variable
            // @see ANALYSIS_REPORT.md SEC-001
            $this->password = 'root11';
        } else {
            $this->host = 'tools.db.svc.wikimedia.cloud';
            $this->dbname = $ts_mycnf['user'] . "__" . $this->db_suffix;
            $this->user = $ts_mycnf['user'];
            $this->password = $ts_mycnf['password'];
        }
        unset($ts_mycnf);

        try {
            $this->db = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}",
                $this->user,
                $this->password
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            pub_test_print($e->getMessage());
            error_log($e->getMessage());
            echo "Unable to connect to the database. Please try again later.";
            exit();
        }
    }

    /**
     * Disables ONLY_FULL_GROUP_BY SQL mode for queries with GROUP BY clauses.
     *
     * This is needed because some legacy queries don't include all non-aggregated
     * columns in the GROUP BY clause, which is required by default in MySQL 5.7+.
     *
     * @param string $sql_query The SQL query to check for GROUP BY clause
     *
     * @return void
     */
    public function disableFullGroupByMode(string $sql_query): void
    {
        $queryUpper = strtoupper($sql_query);
        $hasGroupBy = strpos($queryUpper, 'GROUP BY') !== false;

        if ($hasGroupBy && !$this->groupByModeDisabled) {
            try {
                $this->db->exec(
                    "SET SESSION sql_mode=(SELECT REPLACE(@@SESSION.sql_mode,'ONLY_FULL_GROUP_BY',''))"
                );
                $this->groupByModeDisabled = true;
            } catch (PDOException $e) {
                error_log("Failed to disable ONLY_FULL_GROUP_BY: " . $e->getMessage());
            }
        }
    }

    /**
     * Executes a SQL query and returns results for SELECT statements.
     *
     * For SELECT queries, returns fetched results.
     * For INSERT/UPDATE/DELETE, returns empty array.
     *
     * @param string     $sql_query The SQL query to execute
     * @param array|null $params    Optional prepared statement parameters
     *
     * @return array<int, array<string, mixed>> Query results for SELECT, empty array otherwise
     */
    public function executequery(string $sql_query, ?array $params = null): array
    {
        try {
            $this->disableFullGroupByMode($sql_query);

            $q = $this->db->prepare($sql_query);
            if ($params !== null) {
                $q->execute($params);
            } else {
                $q->execute();
            }

            $queryType = strtoupper(substr(trim($sql_query), 0, 6));
            if ($queryType === 'SELECT') {
                return $q->fetchAll(PDO::FETCH_ASSOC);
            }

            return [];
        } catch (PDOException $e) {
            // SECURITY ISSUE: SQL query exposed in error - @see ANALYSIS_REPORT.md SEC-006
            pub_test_print("sql error:" . $e->getMessage() . "<br>" . $sql_query);
            return [];
        }
    }

    /**
     * Executes a SQL query and always returns fetched results.
     *
     * Unlike executequery(), this always attempts to fetch results
     * regardless of query type.
     *
     * @param string     $sql_query The SQL query to execute
     * @param array|null $params    Optional prepared statement parameters
     *
     * @return array<int, array<string, mixed>> Query results
     */
    public function fetchquery(string $sql_query, ?array $params = null): array
    {
        try {
            $this->disableFullGroupByMode($sql_query);

            $q = $this->db->prepare($sql_query);
            if ($params !== null) {
                $q->execute($params);
            } else {
                $q->execute();
            }

            return $q->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            pub_test_print("sql error:" . $e->getMessage() . "<br>" . $sql_query);
            return [];
        }
    }

    /**
     * Closes database connection on object destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->db = null;
    }
}

/**
 * Determines the database name based on table name.
 *
 * Maps specific tables to their corresponding database suffixes
 * for multi-database support.
 *
 * @param string|null $table_name The table name to look up
 *
 * @return string The database suffix ('mdwiki_new' or 'mdwiki')
 */
function get_dbname(?string $table_name): string
{
    $table_db_mapping = [
        'mdwiki_new' => [
            "missing",
            "missing_by_qids",
            "exists_by_qids",
            "publish_reports",
            "login_attempts",
            "logins",
            "publish_reports_stats",
            "all_qids_titles"
        ],
        'mdwiki' => []
    ];

    if ($table_name !== null) {
        foreach ($table_db_mapping as $db => $tables) {
            if (in_array($table_name, $tables, true)) {
                return $db;
            }
        }
    }

    return 'mdwiki';
}

/**
 * Executes a SQL query (INSERT/UPDATE/DELETE) and returns results for SELECT.
 *
 * Creates a new database connection, executes the query, and returns results.
 * Connection is closed after execution.
 *
 * PERFORMANCE NOTE: Creates new connection per call.
 * @see ANALYSIS_REPORT.md PERF-001
 *
 * @param string        $sql_query The SQL query to execute
 * @param array|null    $params    Optional prepared statement parameters
 * @param string|null   $table_name Optional table name for database selection
 *
 * @return array<int, array<string, mixed>> Query results for SELECT, empty array for others
 *
 * @example
 * $results = execute_query("SELECT * FROM pages WHERE lang = ?", ['en']);
 */
function execute_query(
    string $sql_query,
    ?array $params = null,
    ?string $table_name = null
): array {
    $dbname = get_dbname($table_name);
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    $db = new Database($serverName, $dbname);

    $results = ($params !== null)
        ? $db->executequery($sql_query, $params)
        : $db->executequery($sql_query);

    $db = null;

    return $results;
}

/**
 * Fetches results from a SELECT query.
 *
 * Creates a new database connection, executes the SELECT query,
 * and returns all matching rows.
 *
 * @param string        $sql_query The SELECT query to execute
 * @param array|null    $params    Optional prepared statement parameters
 * @param string|null   $table_name Optional table name for database selection
 *
 * @return array<int, array<string, mixed>> Array of associative arrays representing rows
 *
 * @example
 * $users = fetch_query("SELECT * FROM users WHERE active = ?", [1]);
 * // Returns: [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']]
 */
function fetch_query(
    string $sql_query,
    ?array $params = null,
    ?string $table_name = null
): array {
    $dbname = get_dbname($table_name);
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    $db = new Database($serverName, $dbname);

    $results = ($params !== null)
        ? $db->fetchquery($sql_query, $params)
        : $db->fetchquery($sql_query);

    $db = null;

    return $results;
}
