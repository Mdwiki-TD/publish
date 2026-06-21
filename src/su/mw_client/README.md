# su/mw_client/ - MediaWiki OAuth Client Wrapper

## Project Overview

This module provides a wrapper around the `mediawiki/oauthclient` library for making OAuth-authenticated API calls to MediaWiki instances (Wikipedia, Wikidata). It handles client configuration, access token management, CSRF token retrieval, and authenticated POST requests.

### Purpose
- Create and configure MediaWiki OAuth clients
- Manage OAuth access tokens
- Retrieve CSRF tokens for authenticated edits
- Execute OAuth-authenticated API calls

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `index.php` | `Publish\MediaWikiClient` | Complete OAuth client wrapper with all MediaWiki API interaction functions |

## Architecture

### Functions

| Function | Purpose |
|----------|---------|
| `get_client($domain)` | Creates a configured `MediaWiki\OAuthClient\Client` instance |
| `getAccessToken($access_key, $access_secret)` | Creates a `Token` object from stored credentials |
| `get_edits_token($client, $accessToken, $apiUrl)` | Fetches CSRF token (returns string) |
| `get_csrftoken($client, $access_key, $access_secret, $apiUrl)` | Fetches CSRF token (returns full response array) |
| `post_params($apiParams, $https_domain, $access_key, $access_secret)` | Full OAuth POST flow: client -> token -> CSRF -> API call |

### OAuth Flow
```
post_params()
  → get_client($domain)           // Configure OAuth client
  → get_csrftoken()               // Get CSRF token
    → getAccessToken()            // Create access token
    → makeOAuthCall(tokens)       // Fetch CSRF token from API
  → makeOAuthCall(edit)           // Execute the actual edit
```

## Code Quality Review

### Strengths
- **Complete OAuth flow** - `post_params()` encapsulates the full OAuth handshake
- **Proper error handling for CSRF** - Returns structured error JSON when CSRF token retrieval fails
- **User agent identification** - Sets a descriptive user agent string
- **Uses well-maintained library** - `mediawiki/oauthclient` is the official MediaWiki OAuth client

### Weaknesses
1. **Duplicate CSRF token functions** - `get_edits_token()` and `get_csrftoken()` do nearly the same thing but return different types
2. **No connection pooling** - Each `post_params()` call creates a new client and token objects
3. **No response validation** - `post_params()` returns raw response without checking HTTP status
4. **Mixed return types** - `get_csrftoken()` returns an array but `get_edits_token()` returns a string or null

### Critical Issues

> **WARNING**

1. **Consumer credentials loaded at call time** - `get_client()` reads `CONSUMER_KEY`/`CONSUMER_SECRET` from environment on every call. If environment is not set, the client is created with empty credentials (silent failure).

2. **No URL validation** - `$https_domain` and `$domain` parameters are used directly in URL construction without validation.

3. **`rand()` in error response** (`post_params()` line 83):
   ```php
   $data = ['error' => 'get_csrftoken failed', "rand" => rand(), ...];
   ```
   Using `rand()` is cryptographically weak. While not security-critical here, it's unnecessary noise in error responses.

## Areas That Need Attention
- Consolidate `get_edits_token()` and `get_csrftoken()` into a single function
- Add URL/domain validation before constructing OAuth URLs
- Validate that consumer credentials are set before creating client
- Add return type declarations
- Remove `rand()` from error responses

## Improvement Plan
1. **Quick:** Add domain validation, remove `rand()`, add return types
2. **Medium:** Consolidate CSRF token functions, validate credentials upfront
3. **Long:** Create a `MediaWikiApiClient` class with connection reuse and proper error types

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 6/10 |
| **Security** | 6/10 - OAuth flow correct, but no URL validation |
| **Maintainability** | 5/10 - Duplicate functions, mixed return types |
| **Reliability** | 6/10 - Handles CSRF failures but no retry logic |
