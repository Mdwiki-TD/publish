# su/api/ - Wikipedia API Layer

## Project Overview

This module handles all direct interactions with the MediaWiki API for publishing edits to Wikipedia. It provides OAuth-authenticated edit operations and title information queries.

### Purpose
- Execute authenticated edits to Wikipedia via OAuth
- Query Wikipedia API for page metadata (namespace, page ID)

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `do_edit.php` | `Publish\DoEdit` | Executes OAuth-authenticated edits to Wikipedia |
| `wiki_api.php` | `Publish\WikiApi` | Queries Wikipedia API for title/page information |

## Architecture

### `do_edit.php`
Provides `publish_do_edit($apiParams, $wiki, $access)` which:
1. Creates a MediaWiki OAuth client for the target wiki
2. Obtains an access token from stored credentials
3. Fetches a CSRF edit token
4. Executes the edit via `makeOAuthCall()`

### `wiki_api.php`
Provides `GetTitleInfo($targettitle, $lang)` which:
1. Constructs a `action=query` API request
2. Fetches page data via cURL
3. Returns page metadata (pageid, namespace, title)

## Code Quality Review

### Strengths
- Clean separation between edit operations and query operations
- Proper OAuth token flow (access token -> CSRF token -> edit)
- Uses parameterized API queries via `http_build_query()`

### Weaknesses
- **No error handling in `publish_do_edit()`** - Returns raw decoded JSON without checking for API errors
- **No retry logic** - Failed API calls are not retried
- **`GetTitleInfo` silently returns null** on exceptions without logging

### Critical Issues
- `GetTitleInfo` uses `get_url_curl()` with a 5-second timeout - may fail silently on slow networks
- No validation of `$wiki` parameter before constructing URLs (potential SSRF if `$wiki` is user-controlled, though it's filtered upstream)

## Areas That Need Attention
- Add structured error handling and return types to `publish_do_edit()`
- Add retry logic for transient API failures
- Validate `$lang` parameter to prevent URL manipulation
- Add response status code checking for cURL requests

## Improvement Plan
1. **Quick:** Add return type declarations and error checking to `publish_do_edit()`
2. **Medium:** Implement retry with exponential backoff for API calls
3. **Long:** Create a `MediaWikiApi` class with proper error handling and logging

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 6/10 |
| **Security** | 6/10 - OAuth flow is correct but no URL validation |
| **Maintainability** | 6/10 - Simple and readable |
| **Reliability** | 5/10 - No retry or structured error handling |
