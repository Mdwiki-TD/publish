<?php

namespace Publish\MdwikiSql;

function get_dbname($table_name)
{
    // Load from configuration file or define as class constant
    $table_db_mapping = [
        'DB_NAME_NEW' => [
            "missing",
            "missing_by_qids",
            "exists_by_qids",
            "publish_reports",
            "login_attempts",
            "logins",
            "publish_reports_stats",
            "all_qids_titles"
        ],
        'DB_NAME' => [] // default
    ];

    if ($table_name) {
        foreach ($table_db_mapping as $db => $tables) {
            if (in_array($table_name, $tables)) {
                return $db;
            }
        }
    }

    return 'DB_NAME'; // default
}
