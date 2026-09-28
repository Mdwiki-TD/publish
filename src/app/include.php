<?PHP

include_once __DIR__ . '/../vendor_load.php';

$env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

if ($env === 'development' && file_exists(__DIR__ . '/load_env.php')) {
    include_once __DIR__ . '/load_env.php';
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

include_once __DIR__ . '/cors.php';
include_once __DIR__ . '/text_edit.php';
include_once __DIR__ . '/utils/start_utils.php';

include_once __DIR__ . '/process/process_db_log.php';
include_once __DIR__ . '/process/process_edit.php';
include_once __DIR__ . '/process/start.php';

include_once __DIR__ . '/sql/access_helps.php';
include_once __DIR__ . '/sql/mdwiki_sql.php';
include_once __DIR__ . '/sql/sql.php';

include_once __DIR__ . '/api/WikiApi.php';

include_once __DIR__ . '/bots/index.php';

include_once __DIR__ . '/cxtoken/get_token.php';
include_once __DIR__ . '/cxtoken/token_handler.php';


$home = getenv('HOME') ?: ($_SERVER['HOME'] ?? "");

$workFilePath = getenv("TEXT_WORK_FILE") ?: ($_ENV['TEXT_WORK_FILE'] ?? $home . '/public_html/fix_refs/work.php');

if (file_exists($workFilePath)) {
    include_once $workFilePath;
}
