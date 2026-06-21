# PROJECT AUDIT REPORT

**Project:** MDWiki Publish Repository (`php-publish-repo`)
**Audit Date:** 2026-05-27
**Scope:** Full source code audit of `src/` and all subdirectories (9 modules, 30 PHP files)
**Auditor:** Claude Code - Senior Architecture Review

---

## Executive Summary

The MDWiki Publish Repository is a PHP backend service that publishes Wikipedia articles translated via the ContentTranslation tool on `mdwiki.toolforge.org`. It receives translated wikitext through POST requests, preprocesses it using the external `fix_refs` library, and submits edits to Wikipedia via OAuth-authenticated MediaWiki API calls. On success, it links the published article to Wikidata and logs results to both a MySQL database and date-organized JSON report files.

**Key Technologies:**
- PHP 8.0+ (procedural/functional architecture with PSR-4 namespaces)
- MySQL/MariaDB via PDO
- MediaWiki OAuth 1.0a (`mediawiki/oauthclient`)
- Defuse PHP Encryption for token storage
- cURL for HTTP requests
- PHPUnit 10 + PHPStan level 5 for quality assurance

**Architecture:** The application follows a procedural pipeline pattern. A POST request enters through `index.php`, passes through CORS/secret-key validation, and flows through a sequential pipeline: access validation → revision lookup → wikitext preprocessing → OAuth edit → Wikidata linking → logging. All modules are loaded via manual `include_once` chains from `su/include.php`.

---

## Project Health Assessment

| Dimension | Rating | Assessment |
|-----------|--------|------------|
| **Overall Code Quality** | 5/10 | Functional but inconsistent naming, mixed conventions, no type declarations |
| **Maintainability** | 5/10 | Readable at function level, but tight coupling and global state make changes risky |
| **Scalability** | 4/10 | New DB connection per query, no caching, synchronous HTTP, file-based logging |
| **Security Posture** | 4/10 | Hardcoded credentials in VCS, CORS bypass, no input validation, no rate limiting |
| **Production Readiness** | 5/10 | Currently running in production but with significant unresolved risks |

---

## Cross-Project Analysis

### Shared Architectural Patterns

All 9 modules share these patterns:

1. **PSR-4 namespaced functions** - Every module declares a `Publish\*` namespace and exports standalone functions (not classes). This is unconventional but provides basic encapsulation.

2. **Manual include chains** - `su/include.php` loads all 20+ files via `include_once`. No Composer autoloading is used for application code despite being configured in `composer.json`.

3. **Environment-based configuration** - All secrets loaded via `getenv()` with fallbacks to `$_ENV`. Production uses Toolforge environment, development uses `load_env.php`.

4. **Debug-mode output** - `pub_test_print()` is called throughout all modules. When `?test` is in the request, debug output (including SQL queries and error traces) is sent to the client.

### Repeated Weaknesses Across Modules

| Weakness | Affected Modules | Frequency |
|----------|-----------------|-----------|
| No return type declarations | All 9 modules | 100% |
| Hardcoded admin username `"Mr. Ibrahem"` | `start_utils.php`, `process_edit.php`, `token_handler.php` | 3 modules |
| Duplicated username normalization | `start_utils.php::formatUser()`, `token_handler.php::handle_user_name()` | 2 modules |
| Silent error swallowing (return empty) | `mdwiki_sql.php`, `crypt_helps.php`, `revids_bot.php` | 3 modules |
| Hardcoded Windows paths (`I:/MD_TOOLS/...`) | `load_env.php`, `files_helps.php`, `start.php` | 3 modules |
| `global` variable usage | `crypt_helps.php` | 1 module (critical) |
| No input validation on user data | `start.php`, `index.php`, `token.php` | 3 entry points |

### Common Technical Debt

1. **Procedural architecture without abstraction** - Functions call other functions directly. No interfaces, no dependency injection, no service container. Changing one function's behavior can break callers silently.

2. **Inconsistent naming conventions** - `fetchquery` vs `fetch_query`, `executequery` vs `execute_query`, `camelCase` vs `snake_case` mixed within the same module (`sql/mdwiki_sql.php`).

3. **Dead code and empty files** - `sql/table_name.php` contains only a namespace declaration. `sql/sql.php` is 53 lines. The `$table_name` parameter in `execute_query()` and `fetch_query()` is accepted but never used.

4. **Fragile error detection** - Errors are classified by `strpos()` on `json_encode()`'d output rather than structured error codes.

### Dependency Issues

| Dependency | Version | Concern |
|------------|---------|---------|
| `mediawiki/oauthclient` | ^1.2 | Stable, well-maintained |
| `defuse/php-encryption` | ^2.4 | Stable, but key management is the weak link |
| `firebase/php-jwt` | 7.0.0 | Pinned to exact version, no apparent usage in source |
| `phpunit/phpunit` | ^10.0 | Current, good |
| `phpstan/phpstan` | ^2.1 | Current, good |
| External `fix_refs` | N/A | Loaded from filesystem path, not a Composer dependency |

**Key concern:** The `fix_refs` library is loaded via `include_once` from a filesystem path (`TEXT_WORK_FILE` env var). If the file is missing, the application silently continues without preprocessing. There is no version pinning or integrity check.

### Integration Concerns

1. **External API coupling** - The application makes synchronous HTTP calls to Wikipedia API, Wikidata API, and the mdwiki API (for revision IDs). Each has a 5-second timeout. A slow external service blocks the entire request.

2. **Shared database** - Uses the same `DB_NAME` for all operations (access keys, publish reports, page tracking, QID lookups). No separation of concerns at the database level.

3. **File system coupling** - JSON report files are written to a date-organized directory tree. The `all_pages_revids.json` file (89KB) is loaded and parsed on every request.

---

## Critical Findings

### CRITICAL - Immediate Action Required

#### CRIT-001: Hardcoded Credentials in Version Control

**File:** `src/su/load_env.php`
**Severity:** Critical

```php
putenv('TOOL_TOOLSDB_USER=root');
putenv('TOOL_TOOLSDB_PASSWORD=root11');
putenv('COOKIE_KEY=def000008f0992fd44f7b71bc86a13c50ffa0295fabd0b8b008fc19d75774746ae6ef19e0328d36d9b457496158ae01fa22dc7638759aadf6c45fd4cda76edb865b0222f');
putenv('DECRYPT_KEY=def000001358577eb292b944a354cfe446413d532d4c18c963597a88ec1daeba34080234b36ad1c54269ff04c443b5155c0c122a2c4e95137b12507b924f799bf13d8571');
```

**Impact:** Database credentials and encryption keys are committed to Git history. Anyone with repository access can decrypt all stored OAuth tokens and access the database. Even if removed from HEAD, they persist in Git history.

**Remediation:**
1. Add `load_env.php` to `.gitignore` immediately
2. Rotate ALL exposed credentials (DB password, DECRYPT_KEY, COOKIE_KEY)
3. Re-encrypt all OAuth tokens with the new key
4. Use `git filter-branch` or BFG Repo Cleaner to purge from history

---

#### CRIT-002: Authentication Bypass When Secret Code Is Empty

**File:** `src/index.php:38-40`
**Severity:** Critical

```php
if ($publish_secret_code === '') {
    return true;  // Bypasses auth entirely
}
```

**Impact:** If `PUBLISH_SECRET_CODE` environment variable is not set, the endpoint accepts requests from any source without authentication. This is the default state in development.

**Remediation:** Return `false` when the secret code is empty, or require it to be set in production.

---

#### CRIT-003: CORS Validation Disabled on Main Endpoint

**File:** `src/index.php:17-27`
**Severity:** Critical

The CORS validation block is entirely commented out:
```php
/*
$alowed = is_allowed();
if (!$alowed) { ... }
*/
```

**Impact:** The main publish endpoint accepts requests from any origin. Combined with CRIT-002, this means the endpoint is fully open when `PUBLISH_SECRET_CODE` is not set.

**Remediation:** Uncomment and fix the CORS validation, or rely solely on the secret key mechanism (with CRIT-002 fixed).

---

### HIGH - Address Within 1 Week

#### HIGH-001: CORS Bypass via Substring Matching

**File:** `src/su/cors.php:21`

```php
if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false)
```

An attacker can bypass with `https://evil.com/?medwiki.toolforge.org` or `https://evilmedwiki.toolforge.org.evil.com`.

**Fix:** Use `parse_url($origin, PHP_URL_HOST) === $domain` for exact matching.

---

#### HIGH-002: SQL Query Exposure in Debug Mode

**File:** `src/su/sql/mdwiki_sql.php:96`

```php
pub_test_print("sql error:" . $e->getMessage() . "<br>" . $sql_query);
```

When `?test` is in the URL, full SQL queries including table structure and error messages are sent to the client. No authentication is required to enable debug mode.

**Fix:** Log to file only. Never expose SQL to clients.

---

#### HIGH-003: No Input Validation on POST Data

**File:** `src/su/process/start.php:57-68`

User-supplied `$_POST` data (`title`, `user`, `target`, `text`, `sourcetitle`, `campaign`) flows through the entire pipeline without any validation, length checks, or sanitization.

**Fix:** Add a validation layer that checks required fields, maximum lengths, and allowed characters before processing.

---

#### HIGH-004: New Database Connection Per Query

**File:** `src/su/sql/mdwiki_sql.php:129-150`

Both `execute_query()` and `fetch_query()` create a new `Database` instance (new PDO connection) per call, then immediately destroy it. A typical publish request makes 3-5 database calls.

**Fix:** Implement a connection singleton or pass the Database instance through the call chain.

---

#### HIGH-005: Global Encryption Key State

**File:** `src/su/bots/crypt_helps.php:13-15`

```php
$_decrypt_key_str = getenv("DECRYPT_KEY") ?: '';
$decrypt_key = $_decrypt_key_str ? Key::loadFromAsciiSafeString($_decrypt_key_str) : null;
```

The encryption key is loaded at file include time into a module-level variable, then accessed via `global $decrypt_key` in functions. If `DECRYPT_KEY` is not set, all encryption/decryption silently returns empty strings.

**Fix:** Load the key once in a class constructor or factory, validate it exists, and inject it where needed.

---

### MEDIUM - Address Within 1 Month

| ID | Issue | Location |
|----|-------|----------|
| MED-001 | File path injection in `to_do()` - `$file_name` not sanitized | `bots/files_helps.php:19` |
| MED-002 | SSRF risk - `$sourcetitle` passed directly into URL construction | `bots/revids_bot.php:23` |
| MED-003 | Hardcoded admin fallback `"Mr. Ibrahem"` for Wikidata linking | `process/process_edit.php:41` |
| MED-004 | Directory permissions 0755 (world-readable) for report files | `bots/files_helps.php:38-58` |
| MED-005 | No transaction wrapping for multi-step DB operations | `sql/add_to_db.php` |
| MED-006 | `rand()` used in error responses (weak, unnecessary) | `mw_client/index.php:83` |
| MED-007 | Inconsistent null/empty/error return types across all modules | Multiple files |
| MED-008 | `$wiki` parameter not validated before URL construction | `api/do_edit.php`, `cxtoken/get_token.php` |
| MED-009 | `all_pages_revids.json` (89KB) parsed on every request | `bots/revids_bot.php:45` |
| MED-010 | No rate limiting on publish endpoint | `index.php` |

---

## Strengths

### Well-Designed Components

1. **Parameterized SQL queries** - All database queries use PDO prepared statements with `?` placeholders. This is the single most important security practice and is consistently followed across all modules.

2. **Encrypted token storage** - OAuth tokens are encrypted at rest using `defuse/php-encryption`, a well-vetted library. The `access_keys` table stores encrypted values, and the `user_name_hash` column enables indexed lookups without decrypting.

3. **Table name allowlisting** - Both `find_exists_or_update()` and `InsertPageTarget()` validate table names against a hardcoded allowlist before interpolation, mitigating the SQL injection risk from dynamic table names.

4. **Timing-safe comparison** - The secret key check uses `hash_equals()` instead of `===`, preventing timing attacks.

5. **Wikidata fallback mechanism** - When CSRF token retrieval fails for the original user, the system retries with admin credentials. This provides resilience against transient OAuth issues.

### Good Development Practices

6. **Test infrastructure** - PHPUnit 10 and PHPStan level 5 are configured. There are 17 test files covering unit and integration scenarios across multiple modules.

7. **Environment-based configuration** - All secrets are loaded from environment variables in production. The `load_env.php` file is only loaded when `APP_ENV=development`.

8. **Structured report logging** - Results are logged to both a database table (`publish_reports`) and date-organized JSON files, providing dual audit trails.

9. **Error categorization** - `get_errors_file()` classifies errors into specific categories (protectedpage, titleblacklist, ratelimited, abusefilter, etc.) for structured reporting.

10. **cURL timeout discipline** - HTTP requests have both connection (5s) and total (5s) timeouts set, preventing indefinite blocking.

---

## Improvement Roadmap

### Immediate Fixes (Days 1-3)

| Priority | Action | Impact |
|----------|--------|--------|
| 1 | Add `load_env.php` to `.gitignore` | Prevents future credential commits |
| 2 | Rotate all exposed credentials | Mitigates CRIT-001 |
| 3 | Fix `check_publish_secret_code()` to reject empty secret | Fixes CRIT-002 |
| 4 | Fix CORS validation to use `parse_url()` exact matching | Fixes HIGH-001 |
| 5 | Remove hardcoded Windows paths from `files_helps.php` and `start.php` | Environment portability |

### Short-Term Improvements (Weeks 1-2)

| Priority | Action | Impact |
|----------|--------|--------|
| 6 | Uncomment CORS validation on `index.php` | Fixes CRIT-003 |
| 7 | Add input validation layer for POST parameters | Fixes HIGH-003 |
| 8 | Remove SQL query exposure from debug output | Fixes HIGH-002 |
| 9 | Sanitize `$file_name` in `to_do()` | Fixes MED-001 |
| 10 | Validate `$wiki` parameter against whitelist | Fixes MED-008 |
| 11 | Set directory permissions to 0700 for report files | Fixes MED-004 |
| 12 | Add return type declarations to all functions | Type safety |

### Medium-Term Improvements (Weeks 3-8)

| Priority | Action | Impact |
|----------|--------|--------|
| 13 | Implement database connection singleton | Fixes HIGH-004 |
| 14 | Replace `include_once` chains with Composer autoloading | Maintainability |
| 15 | Refactor `crypt_helps.php` to class-based approach | Fixes HIGH-005 |
| 16 | Extract hardcoded admin username to configuration | Fixes MED-003 |
| 17 | Cache `all_pages_revids.json` in memory with TTL | Fixes MED-009 |
| 18 | Add rate limiting middleware | Fixes MED-010 |
| 19 | Standardize error handling (Result type or exceptions) | Fixes MED-007 |
| 20 | Add transaction wrapping for multi-step DB operations | Fixes MED-005 |
| 21 | Deduplicate username normalization logic | Code quality |
| 22 | Validate `$sourcetitle` before URL construction | Fixes MED-002 |

### Long-Term Strategic Refactoring (Months 2-6)

| Priority | Action | Impact |
|----------|--------|--------|
| 23 | Refactor to class-based architecture with DI container | Architecture |
| 24 | Create `MediaWikiApiClient`, `WikidataClient`, `ReportLogger` service classes | Separation of concerns |
| 25 | Implement async HTTP or queue-based processing for external API calls | Scalability |
| 26 | Add comprehensive unit tests (target 80%+ coverage) | Reliability |
| 27 | Implement structured logging (Monolog) replacing `pub_test_print()` | Observability |
| 28 | Add CI/CD pipeline with PHPStan, PHPUnit, and security scanning | DevOps |
| 29 | Create OpenAPI/Swagger documentation for the API | Documentation |
| 30 | Separate `fix_refs` dependency into a proper Composer package | Dependency management |

### Security Hardening Priority Order

```
1. Credential rotation and VCS purge     [CRIT-001] - Day 1
2. Fix auth bypass on empty secret       [CRIT-002] - Day 1
3. Re-enable and fix CORS                [CRIT-003, HIGH-001] - Day 2
4. Add input validation                  [HIGH-003] - Week 1
5. Remove debug info disclosure          [HIGH-002] - Week 1
6. Sanitize file paths                   [MED-001] - Week 1
7. Validate URL parameters               [MED-002, MED-008] - Week 2
8. Restrict file permissions             [MED-004] - Week 2
9. Add rate limiting                     [MED-010] - Week 3
10. Encrypt report file contents         [SEC-003 from prior audit] - Month 2
```

### DevOps and Testing Recommendations

1. **CI Pipeline:**
   - PHPStan level 5 (already configured, enforce in CI)
   - PHPUnit with coverage reporting
   - Composer audit for dependency vulnerabilities
   - Secret scanning (e.g., `trufflehog` or `gitleaks`)

2. **Environment Management:**
   - Use `.env` files with `.env.example` template
   - Never commit `load_env.php` with real credentials
   - Use different databases for development/production

3. **Monitoring:**
   - Add request logging middleware (method, path, user, status, duration)
   - Monitor publish success/failure rates
   - Alert on unusual error patterns (spike in `noaccess`, `abusefilter`)

4. **Testing:**
   - Add unit tests for `process/start.php` (currently untested)
   - Add integration tests for the full publish flow
   - Add security tests for CORS and auth bypass scenarios

---

## Final Evaluation

| Metric | Score | Notes |
|--------|-------|-------|
| **Overall Project Score** | **5/10** | Functional production system with significant security and architectural debt |
| **Risk Level** | **High** | Credential exposure in VCS, auth bypass, no input validation |
| **Technical Debt Level** | **Medium-High** | Procedural architecture, global state, inconsistent naming, duplicated code |
| **Production Readiness** | **5/10** | Currently serving production traffic but with unresolved critical security issues |
| **Security Score** | **4/10** | 3 critical, 4 high, 6 medium security findings |
| **Maintainability Score** | **5/10** | Readable at function level, but tight coupling and no abstraction layer |
| **Test Coverage** | **5/10** | Test infrastructure exists with 17 test files, but core modules lack coverage |

### Recommended Next Steps

1. **Today:** Add `load_env.php` to `.gitignore`. Fix `check_publish_secret_code()` to reject empty secrets.
2. **This week:** Rotate all exposed credentials. Fix CORS validation. Add input validation.
3. **This month:** Implement connection pooling. Add rate limiting. Standardize error handling.
4. **This quarter:** Begin class-based refactoring. Add comprehensive test coverage. Set up CI/CD.

### Summary

The MDWiki Publish Repository is a working production system that successfully publishes translated Wikipedia articles. Its core strength is the consistent use of parameterized SQL queries and encrypted token storage. However, it carries critical security debt (hardcoded credentials, auth bypass, disabled CORS) and architectural debt (procedural global state, no dependency injection, inconsistent conventions). The immediate priority must be credential rotation and authentication hardening before addressing architectural improvements.

---

*This report was generated based on analysis of 30 PHP source files across 9 modules, existing documentation in `docs/`, and the project's `composer.json`, `phpunit.xml`, and `phpstan.neon` configuration.*
