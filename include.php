<?PHP

if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
};
include_once __DIR__ . '/../auth/vendor_load.php';

include_once __DIR__ . '/bots/mdwiki_sql.php';

include_once __DIR__ . '/config.php';
include_once __DIR__ . '/helps.php';
include_once __DIR__ . '/do_edit.php';
include_once __DIR__ . '/add_to_db.php';
include_once __DIR__ . '/get_token.php';
include_once __DIR__ . '/textfixes/include.php';
include_once __DIR__ . '/bots/wd.php';

$fix_refs_file = __DIR__ . '/fix_refs/index.php';
if (file_exists($fix_refs_file)) {
    include_once $fix_refs_file;
} else {
    include_once __DIR__ . '/../fix_refs/index.php';
}
