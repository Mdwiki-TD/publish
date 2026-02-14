# Critical Issues Summary and Architectural Recommendations

**Project:** MDWiki Publish Repository
**Date:** 2026-02-14

---

## Critical Issues Requiring Immediate Attention

### 1. Security: Hardcoded Database Credentials (SEC-001)
**Location:** `src/bots/mdwiki_sql.php:148`
**Risk:** Credential exposure through version control
**Fix:**
```php
// Replace:
$this->password = 'root11';
// With:
$this->password = getenv('DB_PASSWORD') ?: '';
```

### 2. Security: SQL Injection via Table Name (SEC-002)
**Location:** `src/bots/add_to_db.php:131,139,225-228`
**Risk:** SQL injection if table names are user-controlled
**Fix:**
```php
const ALLOWED_TABLES = ['pages', 'pages_users'];

function validateTableName(string $table): string {
    if (!in_array($table, self::ALLOWED_TABLES, true)) {
        throw new InvalidArgumentException("Invalid table name: $table");
    }
    return $table;
}
```

### 3. Security: OAuth Tokens in World-Readable Files (SEC-003)
**Location:** `src/publish_reports/reports_by_day/**/*.json`
**Risk:** Token theft, account compromise
**Fix:**
- Add `.htaccess` to deny access to JSON files
- Encrypt sensitive data before writing
- Set directory permissions to 0700
- Add to `.gitignore`

### 4. Security: CORS Disabled (SEC-004 + SEC-005)
**Location:** `src/index.php:33-42`
**Risk:** CSRF attacks from any origin
**Fix:**
```php
// Re-enable and fix validation:
$allowed = is_allowed();
if (!$allowed) {
    http_response_code(403);
    exit(json_encode(['error' => 'Access denied']));
}
header("Access-Control-Allow-Origin: https://$allowed");
```

---

## High Priority Logical Errors

### 1. N+1 Decryption Problem (LOG-001)
**Location:** `src/bots/access_helps_new.php:78-97`
**Impact:** O(n) performance degrades with user count
**Fix:**
```sql
-- Add indexed column:
ALTER TABLE keys_new ADD COLUMN username_hash VARCHAR(64) INDEX;
```
```php
// Then query by hash:
$hash = hash('sha256', $user);
$query = "SELECT id, a_k, a_s FROM keys_new WHERE username_hash = ?";
```

### 2. Audit Trail Integrity (LOG-002)
**Location:** `src/bots/process_edit.php:118-156`
**Impact:** Edits attributed to wrong user
**Fix:** Require explicit user consent for fallback or fail the Wikidata operation

### 3. Race Condition in Logging (LOG-003)
**Location:** `src/bots/files_helps.php:41-51`
**Impact:** Concurrent requests may share directories
**Fix:**
```php
function to_do(array $tab, string $file_name): void {
    static $main_dir = null;
    if ($main_dir === null) {
        $rand_id = time() . "-" . bin2hex(random_bytes(6));
        $main_dir = check_dirs($rand_id, "reports_by_day");
    }
    // ... rest of function
}
```

---

## High Priority Performance Issues

### 1. No Database Connection Pooling (PERF-001)
**Location:** `src/bots/mdwiki_sql.php`
**Impact:** Connection overhead on every query
**Fix:** Use persistent connections or singleton pattern
```php
// Add to PDO options:
PDO::ATTR_PERSISTENT => true
```

### 2. Full Table Scan for User Lookup (PERF-002)
**Location:** `src/bots/access_helps_new.php:78`
**Impact:** O(n) query + O(n) decryption
**Fix:** Same as LOG-001 - add username_hash column

---

## Type Safety Improvements

All files have been updated with:
- `declare(strict_types=1)`
- PHPDoc `@param` and `@return` annotations
- Type hints on function parameters and return types
- Property type declarations in classes

### Type Aliases Defined
```php
/** @typedef AccessCredentials array{access_key: string, access_secret: string} */
/** @typedef RequestData array{user?: string, title?: string, target?: string, ...} */
/** @typedef ProcessTab array{title: string, summary: string, lang: string, ...} */
```

---

## Architectural Recommendations

### Short-term (1-2 weeks)
1. Move credentials to environment variables
2. Add table name whitelist validation
3. Re-enable CORS with proper validation
4. Add `.htaccess` protection for report files

### Medium-term (1-2 months)
1. Add `username_hash` column to `keys_new` table
2. Implement connection pooling
3. Standardize error handling (use exceptions or Result type)
4. Add rate limiting middleware

### Long-term (3-6 months)
1. Migrate global variables to dependency injection container
2. Separate concerns into service classes
3. Consolidate `access_helps.php` and `access_helps_new.php`
4. Add comprehensive unit tests
5. Implement proper logging framework (Monolog)

---

## Files Modified

| File | Changes |
|------|---------|
| `src/bots/mdwiki_sql.php` | Added PHPDoc, type annotations, security comments |
| `src/bots/helps.php` | Added PHPDoc, type annotations |
| `src/bots/process_edit.php` | Added PHPDoc, type annotations, constants |
| `src/bots/do_edit.php` | Added PHPDoc, type annotations |
| `src/bots/wd.php` | Added PHPDoc, type annotations |
| `src/bots/access_helps.php` | Added PHPDoc, type annotations |
| `src/bots/access_helps_new.php` | Added PHPDoc, type annotations, performance warnings |
| `src/bots/files_helps.php` | Added PHPDoc, type annotations, race condition warning |
| `src/bots/revids_bot.php` | Added PHPDoc, type annotations |
| `src/bots/add_to_db.php` | Added PHPDoc, type annotations, security warning |
| `src/bots/get_token.php` | Added PHPDoc, type annotations |
| `src/bots/cors.php` | Added PHPDoc, security warnings |
| `src/bots/config.php` | Added PHPDoc, type annotations |
| `src/start.php` | Added PHPDoc, type annotations, typedefs |
| `src/index.php` | Added PHPDoc, security warning |
| `src/include.php` | Added PHPDoc |
| `src/vendor_load.php` | Added PHPDoc |
| `src/main.php` | Added PHPDoc |

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Files analyzed | 23 |
| Security issues found | 12 |
| Logical errors found | 8 |
| Performance issues found | 6 |
| Architectural issues found | 7 |
| Functions documented | 50+ |
| Lines of documentation added | 1000+ |

---

## Next Steps

1. **Immediate:** Address SEC-001 (credentials), SEC-003 (file permissions), SEC-004 (CORS)
2. **This week:** Add table validation (SEC-002), fix race condition (LOG-003)
3. **This month:** Add username_hash column, implement connection pooling
4. **Ongoing:** Add tests, refactor for dependency injection
