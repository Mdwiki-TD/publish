<?php

namespace Publish\MediaWikiClient;

use Publish\MediaWikiClient\MediaWikiOAuthClientAdapter;
use Publish\MediaWikiClient\MediaWikiOAuthClientFactory;

use MediaWiki\OAuthClient\Token;
use function Publish\Helps\pub_test_print;

class MediaWikiEditClient
{
    private MediaWikiOAuthClientFactory $clientFactory;

    /**
     * Inject the client factory from outside instead of building a real
     * OAuth Client inside the class. This lets tests inject a fake factory
     * that returns a fake OAuthHttpClientInterface, avoiding both the real
     * network and real OAuth credentials.
     *
     * If no factory is provided, a real MediaWikiOAuthClientFactory is
     * created automatically so the class still works out of the box in
     * production code.
     */
    public function __construct(?MediaWikiOAuthClientFactory $clientFactory = null)
    {
        $this->clientFactory = $clientFactory ?? new MediaWikiOAuthClientFactory();
    }

    public function getEditsToken(MediaWikiOAuthClientAdapter $client, Token $accessToken, string $apiUrl): ?string
    {
        $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
        $data = json_decode($response);

        if ($data === null || !isset($data->query->tokens->csrftoken)) {
            // Handle error
            pub_test_print('<br>getEditsToken Error: ' . json_last_error() . ' ' . json_last_error_msg());

            return null;
        }

        return $data->query->tokens->csrftoken;
    }

    /**
     * @return array|null Decoded JSON response as an associative array,
     *                     or null if it couldn't be decoded at all.
     */
    private function getCsrfTokenData(MediaWikiOAuthClientAdapter $client, Token $accessToken, string $apiUrl): ?array
    {
        $response = $client->makeOAuthCall($accessToken, "$apiUrl?action=query&meta=tokens&format=json");
        $data = json_decode($response, true);

        if ($data === null || !isset($data['query']['tokens']['csrftoken'])) {
            // Handle error
            pub_test_print('<br>get_csrftoken Error: ' . json_last_error() . ' ' . json_last_error_msg());
            pub_test_print($data);
        }

        return $data;
    }

    public function postParams(array $apiParams, string $httpsDomain, string $accessKey, string $accessSecret): string
    {
        $client = $this->clientFactory->createForDomain($httpsDomain);
        $apiUrl = "$httpsDomain/w/api.php";
        $accessToken = new Token($accessKey, $accessSecret);

        $csrfTokenData = $this->getCsrfTokenData($client, $accessToken, $apiUrl);
        $csrftoken = $csrfTokenData['query']['tokens']['csrftoken'] ?? null;

        if ($csrftoken === null) {
            $data = [
                'error' => 'get_csrftoken failed',
                'rand' => rand(),
                'csrftoken_data' => $csrfTokenData,
            ];

            return json_encode($data, JSON_PRETTY_PRINT);
        }

        $apiParams['format'] = 'json';

        pub_test_print('postParams: apiParams:' . json_encode($apiParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $apiParams['token'] = $csrftoken;

        return $client->makeOAuthCall($accessToken, $apiUrl, true, $apiParams);
    }

    /**
     * Perform an edit on the given wikipedia wiki (e.g. "en", "ar").
     *
     * @return array|null Decoded JSON response, or null if it couldn't be decoded.
     */
    public function publishEdit(array $apiParams, string $wiki, Token $accessToken): ?array
    {
        $client = $this->clientFactory->createForDomain("$wiki.wikipedia.org");

        $apiUrl = "https://$wiki.wikipedia.org/w/api.php";

        // check if apiParams already has a token
        if (!isset($apiParams['token'])) {
            $apiParams['token'] = $this->getEditsToken($client, $accessToken, $apiUrl);
        }

        # Error details: The following tags are not allowed to be manually applied: contenttranslation and contenttranslation-v2
        # $apiParams['tags'] = 'contenttranslation|contenttranslation-v2';

        $req = $client->makeOAuthCall(
            $accessToken,
            $apiUrl,
            true,
            $apiParams
        );

        return json_decode($req, true);
    }
}
