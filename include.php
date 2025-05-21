<?PHP

if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
};
include_once __DIR__ . '/../auth/vendor_load.php';

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

$fix_refs_file = __DIR__ . '/fix_refs/work.php';
$fix_refs_file2 = __DIR__ . '/../fix_refs_repo/work.php';

if (file_exists($fix_refs_file)) {
    include_once $fix_refs_file;
} elseif (file_exists($fix_refs_file2)) {
    include_once $fix_refs_file2;
} else {
    include_once __DIR__ . '/../fix_refs/work.php';
}
