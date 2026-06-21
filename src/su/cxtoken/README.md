# su/cxtoken/ - ContentTranslation Token Management

## Project Overview

This module handles retrieval and management of ContentTranslation (CX) tokens from Wikipedia. CX tokens are required for the ContentTranslation extension to authorize publishing operations on behalf of users.

### Purpose
- Retrieve CX tokens from Wikipedia API for authenticated users
- Handle token errors (invalid authorization, expired tokens)
- Automatically clean up invalid OAuth credentials from the database

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `get_token.php` | `Publish\GetToken` | Fetches CX tokens from Wikipedia API |
| `token_handler.php` | `Publish\TokenHandler` | Orchestrates token retrieval with error handling |

## Architecture

### Request Flow
```
token.php (GET endpoint)
  → CORS validation
  → token_handler.php::handle_token($wiki, $user)
    → Normalize username (handle special users)
    → Look up OAuth credentials from database
    → get_token.php::get_cxtoken($wiki, $access_key, $access_secret)
      → POST to Wikipedia API with action=cxtoken
    → If mwoauth-invalid-authorization: delete credentials from DB
    → Return JSON response
```

### `get_cxtoken($wiki, $access_key, $access_secret)`
Makes an OAuth-authenticated POST request to `https://{$wiki}.wikipedia.org/w/api.php` with `action=cxtoken` to retrieve the CX token.

### `handle_token($wiki, $user)`
Orchestrates the full token retrieval flow:
1. Normalizes special usernames (`"Mr. Ibrahem 1"` -> `"Mr. Ibrahem"`, `"Admin"` -> `"Mr. Ibrahem"`)
2. Retrieves OAuth credentials from database
3. Calls `get_cxtoken()` to fetch the token
4. On `mwoauth-invalid-authorization` errors, deletes the stored credentials

## Code Quality Review

### Strengths
- **Automatic credential cleanup** - Invalid OAuth tokens are removed from the database
- **Username normalization** - Handles admin/alias users consistently
- **Clean error responses** - Returns structured JSON errors with HTTP status codes

### Weaknesses
1. **Duplicate username normalization** - `handle_user_name()` duplicates logic from `start_utils.php::formatUser()`
2. **No return type declarations** - Functions lack explicit return types
3. **Mixed output concerns** - `handle_token()` directly calls `print()` and `exit()`, making it untestable
4. **Double HTTP status setting** - Sets both `http_response_code(403)` and `header('HTTP/1.0 403 Forbidden')`

### Critical Issues
- **No input validation on `$wiki`** - The `$wiki` parameter is used directly in URL construction without validation. A malicious value like `evil.com` could redirect API calls.

## Areas That Need Attention
- Extract username normalization to a shared utility (deduplicate with `start_utils.php`)
- Separate token retrieval from output (`handle_token` should return data, not print it)
- Validate `$wiki` parameter against a whitelist of supported wikis
- Add return type declarations

## Improvement Plan
1. **Quick:** Add `$wiki` validation, remove duplicate HTTP status code setting
2. **Medium:** Extract shared username normalization, add return types
3. **Long:** Refactor to a `TokenService` class that returns data (separate from HTTP layer)

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 6/10 |
| **Security** | 5/10 - No wiki parameter validation |
| **Maintainability** | 6/10 - Simple logic, but duplicated code |
| **Testability** | 4/10 - Direct output makes unit testing difficult |
