<?PHP

if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
};
include_once __DIR__ . '/vendor_load.php';

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
include_once __DIR__ . '/textfixes/include.php';
include_once __DIR__ . '/bots/wd.php';
include_once __DIR__ . '/bots/process_edit.php';

if (substr(__DIR__, 0, 2) == 'I:') {
    include_once 'I:/mdwiki/fix_refs_repo/work.php';
} else {
    include_once __DIR__ . '/../fix_refs/work.php';
}
