<?PHP

include_once __DIR__ . '/../vendor_load.php';

$env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

if ($env === 'development' && file_exists(__DIR__ . '/load_env.php')) {
    include_once __DIR__ . '/load_env.php';
}

if (isset($_REQUEST['test']) && $env === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

include_once __DIR__ . '/config.php';

# MediaWikiClient
include_once __DIR__ . '/MediaWikiClient/MediaWikiOAuthClientAdapter.php';
include_once __DIR__ . '/MediaWikiClient/OAuthHttpClientInterface.php';

include_once __DIR__ . '/MediaWikiClient/MediaWikiOAuthClientFactory.php';
include_once __DIR__ . '/MediaWikiClient/OAuthClientFactoryInterface.php';

include_once __DIR__ . '/MediaWikiClient/MediaWikiEditClient.php';

# CurlRequests
include_once __DIR__ . '/CurlRequests/HttpClientInterface.php';
include_once __DIR__ . '/CurlRequests/CurlHttpClient.php';

# AddToDb
include_once __DIR__ . '/AddToDb/PublishReportsRepository.php';

# MdwikiSql
include_once __DIR__ . '/Database.php';

# Http
include_once __DIR__ . '/Http/PublishEndpoint.php';
include_once __DIR__ . '/Http/CxTokenEndpoint.php';

include_once __DIR__ . '/cors.php';
include_once __DIR__ . '/text_edit.php';
include_once __DIR__ . '/utils/start_utils.php';

include_once __DIR__ . '/process/EditProcessLog.php';
include_once __DIR__ . '/process/ProcessEdit.php';
include_once __DIR__ . '/process/StartController.php';

include_once __DIR__ . '/sql/access_helps.php';
include_once __DIR__ . '/sql/mdwiki_sql.php';

include_once __DIR__ . '/api/WikiApi.php';

include_once __DIR__ . '/bots/include.php';


$home = getenv('HOME') ?: ($_SERVER['HOME'] ?? "");

$workFilePath = getenv("TEXT_WORK_FILE") ?: ($_ENV['TEXT_WORK_FILE'] ?? $home . '/public_html/fix_refs/work.php');

if (file_exists($workFilePath)) {
    include_once $workFilePath;
}
