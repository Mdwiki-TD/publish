<?PHP

if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
};
include_once __DIR__ . '/Citation.php';
include_once __DIR__ . '/md_cat.php';
include_once __DIR__ . '/text_fix_refs.php';
include_once __DIR__ . '/text_fix.php';
