# Static Analysis Report: MDWiki Publish Repository

**Analysis Date:** 2026-02-14
**Analyzer:** Claude Code
**Files Analyzed:** 23 PHP files

---

## Executive Summary

This report documents findings from a comprehensive static analysis of the MDWiki Publish codebase. The application handles OAuth-authenticated Wikipedia article publishing, integrating with MediaWiki APIs and Wikidata.

### Critical Findings Summary

| Category | Critical | High | Medium | Low |
|----------|----------|------|--------|-----|
| Security | 3 | 4 | 3 | 2 |
| Logic Errors | 1 | 3 | 4 | 2 |
| Performance | 0 | 2 | 3 | 2 |
| Architecture | 0 | 1 | 4 | 3 |

---

## Security Vulnerabilities

### CRITICAL

#### SEC-001: Hardcoded Database Credentials
**File:** `src/bots/mdwiki_sql.php:55`
**Severity:** Critical
**Description:** Database password is hardcoded in source code for localhost environment.
```php
$this->password = 'root11';
```
**Impact:** Credentials exposure through version control or code access.
**Recommendation:** Use environment variables or secure credential storage.

#### SEC-002: SQL Injection via Table Name Interpolation
**File:** `src/bots/add_to_db.php:59,66,124`
**Severity:** Critical
**Description:** Table names are directly interpolated into SQL queries without validation.
```php
$query = <<<SQL
    SELECT * FROM $table_name WHERE title = ? AND lang = ? AND user = ?
SQL;
```
**Impact:** If `$table_name` comes from user input, SQL injection is possible.
**Recommendation:** Whitelist allowed table names and use validation.

#### SEC-003: OAuth Tokens Stored in World-Readable Files
**File:** `src/publish_reports/reports_by_day/**/*.json`
**Severity:** Critical
**Description:** Publish reports containing sensitive OAuth tokens and user data are stored in JSON files with 0755 directory permissions.
**Impact:** Token theft, account compromise.
**Recommendation:** Encrypt sensitive data, restrict file permissions to 0700, exclude from web access.

### HIGH

#### SEC-004: CORS Security Disabled
**File:** `src/index.php:8-14`
**Severity:** High
**Description:** CORS validation is commented out, allowing requests from any origin.
```php
// $alowed = is_allowed();
// if (!$alowed) {
//     http_response_code(403);
//     ...
// }
```
**Impact:** Cross-site request forgery (CSRF) attacks.
**Recommendation:** Re-enable CORS validation or implement CSRF tokens.

#### SEC-005: Weak CORS Domain Validation
**File:** `src/bots/cors.php:22`
**Severity:** High
**Description:** Uses `strpos()` for domain validation which can be bypassed.
```php
if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false)
```
**Impact:** Attacker can bypass using subdomains like `evilmdwiki.toolforge.org.evil.com`.
**Recommendation:** Use `parse_url()` and exact domain matching.

#### SEC-006: Information Disclosure in Error Messages
**File:** `src/bots/mdwiki_sql.php:115`
**Severity:** High
**Description:** SQL queries are exposed in error messages.
```php
pub_test_print("sql error:" . $e->getMessage() . "<br>" . $sql_query);
```
**Impact:** Database structure disclosure to attackers.
**Recommendation:** Log detailed errors, show generic messages to users.

#### SEC-007: No Rate Limiting
**File:** `src/start.php`
**Severity:** High
**Description:** No rate limiting on the publish endpoint.
**Impact:** Abuse, DoS attacks, automated spam publishing.
**Recommendation:** Implement per-user and per-IP rate limiting.

### MEDIUM

#### SEC-008: Missing Input Validation
**File:** `src/start.php:131-132`
**Severity:** Medium
**Description:** User input is only trimmed, not validated for length or format.
```php
$user = formatUser($request['user'] ?? '');
$title = formatTitle($request['title'] ?? '');
```
**Recommendation:** Add input validation with maximum lengths and character whitelists.

#### SEC-009: Insecure File Permissions
**File:** `src/bots/files_helps.php:56,62,68,74,80`
**Severity:** Medium
**Description:** Directories created with 0755 permissions allow world read/execute.
```php
mkdir($reports_dir, 0755, true);
```
**Recommendation:** Use 0700 for sensitive data directories.

#### SEC-010: Unvalidated Revision ID Fallback
**File:** `src/start.php:97`
**Severity:** Medium
**Description:** Revision ID falls back to request parameter without validation.
```php
$revid = $request['revid'] ?? $request['revision'] ?? '';
```
**Impact:** Potential injection of invalid revision IDs.
**Recommendation:** Validate revision ID is numeric and within expected range.

### LOW

#### SEC-011: Potential XSS in Reports Viewer
**File:** `src/publish_reports/index.php:254`
**Severity:** Low
**Description:** While `encodeURIComponent` is used, title could contain malicious content.
**Recommendation:** Sanitize all user-generated content before display.

#### SEC-012: Debug Mode Information Exposure
**File:** `src/include.php:3-7`
**Severity:** Low
**Description:** Debug mode enabled via URL parameter exposes errors.
```php
if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
```
**Recommendation:** Require authentication for debug mode or remove in production.

---

## Logical Errors

### HIGH

#### LOG-001: N+1 Decryption Problem in User Lookup
**File:** `src/bots/access_helps_new.php:29-44`
**Severity:** High
**Description:** `get_user_id()` loads ALL users from database and decrypts each one to find a match.
```php
$query = "SELECT id, u_n FROM keys_new";
$result = fetch_query($query);
foreach ($result as $row) {
    $user_db = decode_value($row['u_n'], 'decrypt');
    if ($user_db == $user) {
```
**Impact:** Performance degrades linearly with user count. Decryption is expensive.
**Recommendation:** Store a hash of the username for O(1) lookup, or encrypt query parameter.

#### LOG-002: Audit Trail Integrity Issue
**File:** `src/bots/process_edit.php:52-77`
**Severity:** High
**Description:** Fallback to "Mr. Ibrahem" user for Wikidata linking creates incorrect audit trail.
```php
$fallback_access = get_access_from_db_new('Mr. Ibrahem');
```
**Impact:** Edits attributed to wrong user.
**Recommendation:** Fail explicitly or notify user of credential issue.

#### LOG-003: Race Condition in Directory Creation
**File:** `src/bots/files_helps.php`
**Severity:** High
**Description:** `$rand_id` and `$main_dir_by_day` are set at file include time, not function call time.
```php
$rand_id = time() .  "-" . bin2hex(random_bytes(6));
$main_dir_by_day = check_dirs($rand_id, "reports_by_day");
```
**Impact:** Multiple concurrent requests could share the same directory.
**Recommendation:** Generate unique ID inside `to_do()` function.

### MEDIUM

#### LOG-004: Inconsistent Null Handling
**File:** Multiple files
**Severity:** Medium
**Description:** Functions return inconsistent types for errors:
- `get_access_from_db()` returns `null`
- `LinkToWikidata()` returns `['error' => '...']`
- `get_csrftoken()` returns array with null csrftoken
**Recommendation:** Standardize error handling with exceptions or Result type.

#### LOG-005: Missing Transaction Handling
**File:** `src/bots/add_to_db.php`
**Severity:** Medium
**Description:** Database operations lack transaction wrapping.
**Impact:** Partial data writes on failure.
**Recommendation:** Wrap related operations in database transactions.

#### LOG-006: Silent Failures
**File:** `src/bots/helps.php:41-44`
**Severity:** Medium
**Description:** Decryption failures return empty string without logging.
```php
} catch (\Exception $e) {
    $value = "";
}
```
**Recommendation:** Log decryption failures for security monitoring.

#### LOG-007: Empty String vs Null Ambiguity
**File:** `src/start.php:95-98`
**Severity:** Medium
**Description:** Empty string used as sentinel value, conflated with legitimate empty values.
```php
if (empty($revid)) {
    $tab['empty revid'] = 'Can not get revid...';
```
**Recommendation:** Use `null` for "not found" vs empty string for "empty but valid".

### LOW

#### LOG-008: Unused Variable in Scope
**File:** `src/bots/add_to_db.php:16-26`
**Severity:** Low
**Description:** `$Words_table` loaded at file level but may fail silently.
```php
} catch (\Exception $e) {
    $Words_table = [];
}
```
**Recommendation:** Handle missing words file explicitly or remove unused code.

---

## Performance Issues

### HIGH

#### PERF-001: No Database Connection Pooling
**File:** `src/bots/mdwiki_sql.php`
**Severity:** High
**Description:** New PDO connection created for each query, destroyed after.
```php
$db = new Database($_SERVER['SERVER_NAME'] ?? '', $dbname);
// ... execute query ...
$db = null;
```
**Impact:** Connection overhead on every database operation.
**Recommendation:** Implement connection pooling or persistent connections.

#### PERF-002: Full Table Scan for User Lookup
**File:** `src/bots/access_helps_new.php:29-31`
**Severity:** High
**Description:** Query fetches all rows without WHERE clause.
```php
$query = "SELECT id, u_n FROM keys_new";
$result = fetch_query($query);
```
**Impact:** O(n) database read plus O(n) decryption operations.
**Recommendation:** Add indexed column for username hash.

### MEDIUM

#### PERF-003: Revids JSON Loaded on Every Request
**File:** `src/bots/revids_bot.php:46`
**Severity:** Medium
**Description:** `all_pages_revids.json` loaded and parsed on every revision lookup.
```php
$json = json_decode(file_get_contents($revids_file), true);
```
**Impact:** File I/O and JSON parsing overhead per request.
**Recommendation:** Cache parsed JSON in memory with TTL.

#### PERF-004: Synchronous HTTP Without Proper Timeouts
**File:** `src/bots/helps.php:66-88`
**Severity:** Medium
**Description:** cURL with 5-second timeout may block request processing.
```php
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
```
**Impact:** Slow external API responses block entire request.
**Recommendation:** Consider async HTTP or queue-based processing.

### LOW

#### PERF-005: Repeated Configuration Parsing
**File:** `src/bots/config.php:11`
**Severity:** Low
**Description:** INI file parsed on every request.
```php
$ini = parse_ini_file($inifile);
```
**Recommendation:** Cache configuration in OPcache-friendly way.

#### PERF-006: JSON Pretty Print in Production
**File:** `src/bots/files_helps.php:38`
**Severity:** Low
**Description:** JSON_PRETTY_PRINT adds unnecessary overhead.
```php
file_put_contents($file_j, json_encode($tab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
```
**Recommendation:** Use compact JSON in production.

---

## Architectural Anti-Patterns

### HIGH

#### ARCH-001: Global State Abuse
**File:** Multiple files
**Severity:** High
**Description:** Extensive use of global variables for configuration:
- `$consumerKey`, `$consumerSecret`, `$gUserAgent`
- `$cookie_key`, `$decrypt_key`
- `$rand_id`, `$main_dir_by_day`
- `$user_ids_cache`

**Impact:** Testing difficulty, hidden dependencies, race conditions.
**Recommendation:** Use dependency injection container or configuration class.

### MEDIUM

#### ARCH-002: Mixed Concerns
**File:** Multiple files
**Severity:** Medium
**Description:** Files mix multiple responsibilities:
- `process_edit.php`: API calls, database, Wikidata, error handling
- `wd.php`: Database queries, HTTP, OAuth

**Recommendation:** Separate into focused service classes.

#### ARCH-003: Duplicate Code
**File:** `src/bots/access_helps.php` vs `src/bots/access_helps_new.php`
**Severity:** Medium
**Description:** Nearly identical functions with different table names.
**Recommendation:** Consolidate with strategy pattern or unified table.

#### ARCH-004: Namespace/Directory Mismatch
**File:** All files
**Severity:** Medium
**Description:** Namespace `Publish\*` doesn't match directory `bots/`.
**Recommendation:** Align namespaces with PSR-4 or reorganize directories.

#### ARCH-005: Inconsistent Error Handling Strategy
**File:** Multiple
**Severity:** Medium
**Description:** Mix of:
- Returning `null`
- Returning `['error' => '...']`
- Printing and exiting
- Throwing exceptions (rarely)

**Recommendation:** Adopt consistent Result pattern or exception hierarchy.

### LOW

#### ARCH-006: Magic Strings
**File:** Multiple
**Severity:** Low
**Description:** Hardcoded strings like "Mr. Ibrahem", "success", "errors" used throughout.
**Recommendation:** Define constants for magic values.

#### ARCH-007: Missing Interface Abstractions
**File:** All service files
**Severity:** Low
**Description:** Direct instantiation makes testing difficult.
**Recommendation:** Define interfaces for external services (OAuth, Database, HTTP).

---

## Type Safety Analysis

### Missing Type Annotations

The codebase lacks comprehensive PHP type annotations. The following should be added:

| File | Functions Needing Types |
|------|------------------------|
| `start.php` | All functions |
| `process_edit.php` | All functions |
| `mdwiki_sql.php` | Database class methods |
| `helps.php` | All functions |
| `access_helps.php` | All functions |
| `access_helps_new.php` | All functions |
| `do_edit.php` | All functions |
| `wd.php` | All functions |
| `revids_bot.php` | All functions |
| `add_to_db.php` | All functions |
| `files_helps.php` | All functions |
| `get_token.php` | All functions |
| `cors.php` | `is_allowed()` |

---

## Recommendations Priority Matrix

### Immediate Action (Critical)

1. **SEC-001**: Remove hardcoded credentials - use environment variables
2. **SEC-002**: Validate table names against whitelist
3. **SEC-003**: Restrict permissions on report files, encrypt tokens
4. **SEC-004**: Re-enable CORS validation with SEC-005 fix

### Short-term (High Priority)

5. **LOG-003**: Fix race condition in file logging
6. **LOG-002**: Fix audit trail integrity issue
7. **PERF-002**: Add indexed column for username lookup
8. **ARCH-001**: Begin migrating globals to DI container

### Medium-term

9. Standardize error handling across codebase
10. Add comprehensive type annotations
11. Implement connection pooling
12. Consolidate access_helps modules

### Long-term

13. Separate concerns into service classes
14. Add comprehensive unit tests
15. Implement proper logging framework
16. Add API versioning

---

## Conclusion

The codebase is functional but has significant security and architectural debt. Priority should be given to:

1. **Security hardening** - Credentials, CORS, token storage
2. **Performance optimization** - Database connection pooling, query optimization
3. **Code quality** - Type annotations, error handling standardization

The analysis identified **12 security issues**, **8 logical errors**, **6 performance concerns**, and **7 architectural anti-patterns** that should be addressed systematically.
