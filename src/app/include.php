<?PHP

include_once __DIR__ . '/../vendor_load.php';

$env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

if ($env === 'development' && file_exists(__DIR__ . '/load_env.php')) {
    include_once __DIR__ . '/load_env.php';
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Publish\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

include_once __DIR__ . '/text_edit.php';
include_once __DIR__ . '/utils/start_utils.php';

include_once __DIR__ . '/MdwikiSql/access_helps.php';
include_once __DIR__ . '/MdwikiSql/mdwiki_sql.php';

include_once __DIR__ . '/api/WikiApi.php';

include_once __DIR__ . '/bots/include.php';

$home = getenv('HOME') ?: ($_SERVER['HOME'] ?? "");

$workFilePath = getenv("TEXT_WORK_FILE") ?: ($_ENV['TEXT_WORK_FILE'] ?? $home . '/public_html/fix_refs/work.php');

if (file_exists($workFilePath)) {
    include_once $workFilePath;
}
