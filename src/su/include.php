<?PHP

include_once __DIR__ . '/../vendor_load.php';

$env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

if ($env === 'development' && file_exists(__DIR__ . '/load_env.php')) {
    include_once __DIR__ . '/load_env.php';
}

include_once __DIR__ . '/../bots/include.php';

include_once __DIR__ . '/text_edit.php';
include_once __DIR__ . '/token_handler.php';
