<?PHP

if (substr(__DIR__, 0, 2) == 'I:') {
    include_once 'I:/mdwiki/auth_repo/src/vendor_load.php';
} else {
    include_once __DIR__ . '/../auth/vendor_load.php';
}
