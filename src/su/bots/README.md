# su/bots/ - Utility & Helper Modules

## Project Overview

This module contains utility functions and helper classes used throughout the application. It provides HTTP requests, file I/O, encryption, revision ID lookup, and Wikidata integration.

### Purpose
- cURL HTTP client for external API calls
- File-based report logging with date-organized directory structure
- Encryption/decryption of OAuth tokens
- Revision ID retrieval (from JSON file or API)
- Wikidata sitelink management

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `index.php` | *(loader)* | Includes all bot modules |
| `curl_requests.php` | `Publish\CurlRequests` | HTTP GET via cURL with custom user agent |
| `files_helps.php` | `Publish\FilesHelps` | Report file logging with date-based directory structure |
| `helps.php` | `Publish\Helps` | Debug output helper (`pub_test_print`) |
| `revids_bot.php` | `Publish\Revids` | Revision ID lookup from JSON file or remote API |
| `wd.php` | `Publish\WD` | Wikidata integration - links articles via `wbsetsitelink` |
| `crypt_helps.php` | `Publish\CryptHelps` | Symmetric encryption/decryption using `defuse/php-encryption` |

## Architecture

### Module Relationships
```
curl_requests.php ──→ (used by revids_bot.php, wiki_api.php)
crypt_helps.php   ──→ (used by sql/access_helps.php)
files_helps.php   ──→ (used by process/process_edit.php)
wd.php            ──→ (used by process/process_edit.php)
revids_bot.php    ──→ (used by process/start.php)
helps.php         ──→ (used by nearly all modules)
```

### `wd.php` - Wikidata Integration
The most complex module. `LinkToWikidata()`:
1. Looks up the Wikidata QID for the source article from the database
2. Retrieves OAuth credentials (from parameters or database)
3. Constructs `wbsetsitelink` API parameters
4. Posts to Wikidata API via MediaWiki OAuth client

Includes fallback logic: if CSRF token retrieval fails, retries with admin credentials.

### `crypt_helps.php` - Token Encryption
Uses `defuse/php-encryption` for symmetric encryption of OAuth tokens:
- `encode_value($value)` - Encrypts a string using the `DECRYPT_KEY`
- `decode_value($value)` - Decrypts a string

## Code Quality Review

### Strengths
- **`files_helps.php`** - Clean directory structure creation with proper `mkdir()` permissions
- **`wd.php`** - Good fallback pattern for CSRF failures
- **`crypt_helps.php`** - Uses a well-vetted encryption library
- **`curl_requests.php`** - Sets proper timeouts (5s connect, 5s total)
- **`revids_bot.php`** - Dual lookup strategy (local JSON file, then remote API)

### Weaknesses
1. **Global state in `crypt_helps.php`** - Uses `global $decrypt_key` instead of dependency injection
2. **No return type declarations** - Most functions lack explicit return types
3. **`get_url_curl()` returns `string|false`** - Callers don't always check for `false`
4. **Hardcoded Windows paths** in `files_helps.php` and `revids_bot.php`
5. **`to_do()` filename injection** - `$file_name` parameter is used directly in file path without sanitization

### Critical Issues

> **WARNING**

1. **Global encryption key** (`crypt_helps.php`):
   ```php
   $_decrypt_key_str = getenv("DECRYPT_KEY") ?: '';
   $decrypt_key = $_decrypt_key_str ? Key::loadFromAsciiSafeString($_decrypt_key_str) : null;
   ```
   Loaded at file include time. If `DECRYPT_KEY` is not set, all encryption/decryption silently fails (returns empty string).

2. **File path injection** (`files_helps.php`):
   ```php
   $file_j = $main_dir_by_day . "/$file_name.json";
   ```
   `$file_name` comes from error classification logic and could contain path traversal characters if error messages are crafted maliciously.

3. **`revids_bot.php` - SSRF via `$sourcetitle`**:
   ```php
   $url = "https://mdwiki.toolforge.org/api.php?" . http_build_query($params);
   ```
   `$sourcetitle` is user-supplied and passed directly into URL construction.

## Areas That Need Attention
- Replace `global $decrypt_key` with a class-based approach or closure
- Sanitize `$file_name` in `to_do()` to prevent path traversal
- Add return type declarations to all functions
- Validate `$sourcetitle` before using in URL construction
- Remove hardcoded Windows paths (use environment variables consistently)

## Improvement Plan
1. **Quick:** Add `$file_name` sanitization in `to_do()`, add return types
2. **Medium:** Refactor `crypt_helps.php` to use a class with injected key
3. **Long:** Create a `ReportLogger` class, a `WikidataClient` class, and an `HttpClient` interface

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 5/10 |
| **Security** | 4/10 - Global crypto key, path injection risk |
| **Maintainability** | 5/10 - Procedural, global state |
| **Reliability** | 6/10 - Good fallback patterns in wd.php |
