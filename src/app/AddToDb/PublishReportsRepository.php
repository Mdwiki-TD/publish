<?php
namespace Publish\AddToDb;

use Publish\MdwikiSql\Database;

class PublishReportsRepository
{
    /**
     * Table names allowed to be written to in insertPageTarget.
     * (Same list as before, kept as a constant instead of being hardcoded inside the method)
     */
    private const ALLOWED_TABLES = ['pages', 'pages_users'];

    private Database $db;

    /**
     * Inject the Database instance from outside instead of creating it inside the class.
     * This allows tests to pass a mock/fake instead of a real database connection.
     *
     * If no Database is provided, a real one is created automatically so the
     * class still works out of the box in production code, e.g.:
     *     new PublishReportsRepository()
     */
    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? new Database('DB_NAME');
    }

    /**
     * Single entry point for executing any query.
     * Private because it's an internal implementation detail, not part of the class's public API.
     */
    private function executeQuery(string $sqlQuery, ?array $params = null): bool
    {
        return (bool) $this->db->executeQuery($sqlQuery, $params);
    }

    public function insertPublishReports(
        string $title,
        string $user,
        string $lang,
        string $sourcetitle,
        string $result,
        $data
    ): bool {
        // Validate required parameters
        /*
        if (empty($title) || empty($user) || empty($lang)) {
            error_log("InsertPublishReports: Missing required parameters");
            return false;
        }
        */
        $query = "INSERT INTO publish_reports (`date`, `title`, `user`, `lang`, `sourcetitle`, `result`, `data`) VALUES (NOW(), ?, ?, ?, ?, ?, ?)";

        $reportData = json_encode($data);

        // Remove .json from $result
        $result = str_replace('.json', '', $result);

        $params = [$title, $user, $lang, $sourcetitle, $result, $reportData];

        return $this->executeQuery($query, $params);
    }

    public function insertPageTarget(
        string $sourcetitle,
        string $trType,
        string $cat,
        string $lang,
        string $user,
        string $target,
        string $tableName,
        string $mdwikiRevid,
        string $words
    ): bool {
        if (! in_array($tableName, self::ALLOWED_TABLES, true)) {
            error_log("InsertPageTarget: Invalid table name: $tableName");
            return false;
        }

        // $tableName was already validated above against a strict (===) whitelist before
        // being interpolated into the SQL, so there's no need to bind it as a placeholder
        // (SQL doesn't support binding table/column names anyway).
        $query = <<<SQL
            INSERT INTO $tableName (title, word, translate_type, cat, lang, user, pupdate, target, mdwiki_revid)
            SELECT ?, ?, ?, ?, ?, ?, DATE(NOW()), ?, ?
        SQL;

        $params = [
            $sourcetitle,
            $words,
            $trType,
            $cat,
            $lang,
            $user,
            $target,
            $mdwikiRevid,
        ];

        return $this->executeQuery($query, $params);
    }
}
