<?PHP

$vendorPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendorPath)) {
    $vendorPath = dirname(__DIR__) . '/vendor/autoload.php';
}
if (!file_exists($vendorPath)) {
    $vendorPath = dirname(__DIR__) . '/auth/vendor_load.php';
}
require $vendorPath;
