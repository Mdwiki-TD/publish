<?PHP

include_once __DIR__ . '/vendor_load.php';

$env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

if ($env === 'development' && file_exists(__DIR__ . '/load_env.php')) {
    include_once __DIR__ . '/load_env.php';
}

include_once __DIR__ . '/bots/mdwiki_sql.php';

include_once __DIR__ . '/bots/config.php';
include_once __DIR__ . '/bots/helps.php';
include_once __DIR__ . '/bots/revids_bot.php';
include_once __DIR__ . '/bots/files_helps.php';
include_once __DIR__ . '/bots/access_helps.php';
include_once __DIR__ . '/bots/access_helps_new.php';
include_once __DIR__ . '/bots/do_edit.php';
include_once __DIR__ . '/bots/add_to_db.php';
include_once __DIR__ . '/bots/get_token.php';
include_once __DIR__ . '/bots/wd.php';
include_once __DIR__ . '/bots/process_edit.php';
include_once __DIR__ . '/text_edit.php';
