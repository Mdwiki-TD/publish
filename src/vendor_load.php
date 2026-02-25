<?PHP

$vendor_path = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendor_path)) {
    $vendor_path = dirname(__DIR__) . '/vendor/autoload.php';
}
if (!file_exists($vendor_path)) {
    $vendor_path = dirname(__DIR__) . '/auth/vendor_load.php';
}
require $vendor_path;
