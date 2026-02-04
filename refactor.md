# Refactoring Plan for mdwiki publish-repo

## Project Overview
This is a PHP application that manages the final steps in publishing Wikipedia articles translated using the ContentTranslation tool. It processes translated wikitext, refines it, and publishes to Wikipedia with Wikidata integration.

---

## Critical Issues Identified

### 1. Security Vulnerabilities

#### 1.1 CORS Protection Disabled
**Location:** `src/index.php:8-14`
```php
// $alowed = is_allowed();
// if (!$alowed) { ... }
```
**Severity:** HIGH
**Issue:** CORS domain checking is completely commented out, allowing requests from any origin.
**Recommendation:** Re-enable CORS checking and validate origins properly.

#### 1.2 SQL Injection Risk
**Location:** `src/bots/add_to_db.php:124-127`
**Severity:** HIGH
**Issue:** Table name is interpolated directly into SQL:
```php
$query = <<<SQL
    INSERT INTO $table_name (title, word, translate_type, cat, lang, user, pupdate, target, mdwiki_revid)
```
**Recommendation:** Whitelist table names or use a proper query builder.

#### 1.3 Hardcoded Credentials
**Location:** `src/bots/mdwiki_sql.php:54-55`
```php
$this->user = 'root';
$this->password = 'root11';
```
**Severity:** CRITICAL
**Issue:** Database credentials hardcoded for localhost environment.
**Recommendation:** Use environment variables for all credentials.

#### 1.4 Path Traversal Vulnerability
**Location:** `src/bots/files_helps.php:44-84`
**Severity:** MEDIUM
**Issue:** Directory paths are constructed from user input without proper validation.
**Recommendation:** Validate and sanitize all file paths, use `basename()` for filenames.

#### 1.5 Insecure Error Handling
**Location:** `src/bots/mdwiki_sql.php:68-75`
**Severity:** MEDIUM
**Issue:** Database errors are exposed to users:
```php
echo "Unable to connect to the database. Please try again later.";
```
**Recommendation:** Log errors server-side, show generic messages to users.

---

### 2. Code Quality Issues

#### 2.1 Hardcoded File Paths (Environment-Specific Code)
**Locations:** Multiple files
- `src/include.php:25-29` - Conditional paths based on drive letter
- `src/bots/files_helps.php:49` - `I:/mdwiki/publish-repo/src`
- `src/bots/add_to_db.php:16-19` - Fallback paths for words.json

**Severity:** HIGH
**Issue:** Code contains Windows-specific paths (`I:/mdwiki/...`) that won't work in production.
**Recommendation:** Use dependency injection for paths, environment-based configuration.

#### 2.2 Global State and Variables
**Locations:** Throughout the codebase
- `src/bots/config.php:7-52` - Global configuration variables
- `src/bots/helps.php:15` - Global `$usr_agent`
- `src/bots/add_to_db.php:15-26` - Global `$Words_table`
- `src/bots/files_helps.php:12-15` - Global `$rand_id`, `$main_dir_by_day`
- `src/bots/access_helps_new.php:17` - Global `$user_ids_cache`

**Severity:** HIGH
**Issue:** Heavy reliance on global state makes code:
- Hard to test
- Prone to race conditions
- Difficult to reason about
- Impossible to run multiple instances

**Recommendation:** Use dependency injection container, request-scoped services.

#### 2.3 Duplicate Code
**Locations:**
- `src/bots/access_helps.php` vs `src/bots/access_helps_new.php` - Nearly identical functionality
- `src/bots/mdwiki_sql.php:92-118` - `executequery()` and `fetchquery()` are very similar

**Severity:** MEDIUM
**Issue:** Code duplication increases maintenance burden and bug surface area.
**Recommendation:** Consolidate duplicate functions, use strategy pattern for access helpers.

#### 2.4 Poor Error Handling
**Locations:** Throughout
```php
try {
    $value = Crypto::decrypt($value, $use_key);
} catch (\Exception $e) {
    $value = "";  // Silent failure - insecure!
}
```
**Severity:** HIGH
**Issue:** Exceptions are caught and silently ignored, masking failures.
**Recommendation:** Implement proper error logging, let errors propagate appropriately.

#### 2.5 Mixed Arabic Comments
**Locations:** `src/bots/access_helps.php:19-42`
```php
// تأكد من تنسيق اسم المستخدم
// SQL للاستعلام عن access_key و access_secret بناءً على اسم المستخدم
```
**Severity:** LOW
**Issue:** Inconsistent comment language affects code maintainability.
**Recommendation:** Standardize on English for all code comments.

#### 2.6 Magic Numbers and Strings
**Locations:** Throughout
- Timeout values: `CURLOPT_CONNECTTIMEOUT, 5` (hardcoded)
- Directory names: `"reports_by_day"`, `"publish_reports"`
- Error codes: `"noaccess"`, `"success"`, `"captcha"`

**Severity:** MEDIUM
**Issue:** Magic values make code harder to maintain and modify.
**Recommendation:** Define constants or use configuration files.

#### 2.7 Lack of Type Hints
**Severity:** MEDIUM
**Issue:** Most function parameters lack type hints, making code error-prone.
**Recommendation:** Add full type hints for PHP 8.1+ compatibility.

---

### 3. Architecture Issues

#### 3.1 No Dependency Injection
**Severity:** HIGH
**Issue:** All dependencies are hardcoded or included directly.
**Recommendation:** Implement a DI container (e.g., PHP-DI).

#### 3.2 Tight Coupling
**Locations:** Throughout
- Database class is tightly coupled to specific table structures
- Controllers directly call database functions
- File I/O mixed with business logic

**Severity:** HIGH
**Issue:** Changes in one layer require changes in multiple places.
**Recommendation:** Implement Repository pattern, Service layer.

#### 3.3 No Clear Layering
**Severity:** HIGH
**Issue:** Business logic, data access, and presentation are mixed throughout.
**Recommendation:** Implement clear separation:
- Controller layer (HTTP handling)
- Service layer (business logic)
- Repository layer (data access)

#### 3.4 No Interfaces or Abstractions
**Severity:** MEDIUM
**Issue:** Concrete implementations are used everywhere, making testing difficult.
**Recommendation:** Define interfaces for services and repositories.

#### 3.5 God Objects
**Location:** `src/bots/mdwiki_sql.php` - Database class handles connection, queries, and result processing
**Severity:** MEDIUM
**Issue:** Single Responsibility Principle violation.
**Recommendation:** Split into Connection, QueryBuilder, and ResultSet classes.

---

### 4. Performance Issues

#### 4.1 Inefficient Database Queries
**Location:** `src/bots/access_helps_new.php:19-47`
```php
$query = "SELECT id, u_n FROM keys_new";  // Fetches ALL rows
$result = fetch_query($query);
foreach ($result as $row) { ... }  // Then filters in PHP
```
**Severity:** MEDIUM
**Issue:** Fetches entire table to find one user.
**Recommendation:** Filter in SQL with WHERE clause.

#### 4.2 No Connection Pooling
**Severity:** MEDIUM
**Issue:** New database connection created for each query.
**Recommendation:** Implement connection pooling or singleton pattern for DB connections.

#### 4.3 File I/O on Every Request
**Location:** `src/bots/files_helps.php:17-84`
**Severity:** MEDIUM
**Issue:** Directory creation and file writing happens synchronously on every request.
**Recommendation:** Queue file operations, use async processing.

#### 4.4 Blocking cURL Calls
**Location:** `src/bots/helps.php:66-88`
**Severity:** LOW
**Issue:** Synchronous HTTP requests block execution.
**Recommendation:** Consider async HTTP client or queue external API calls.

---

### 5. Testing and Maintainability Issues

#### 5.1 No Tests
**Severity:** HIGH
**Issue:** No unit tests, integration tests, or test coverage visible.
**Recommendation:** Implement PHPUnit tests with at least 80% coverage.

#### 5.2 Hard to Test Due to Globals
**Severity:** HIGH
**Issue:** Global state makes isolated testing impossible.
**Recommendation:** Remove all global variables, use DI.

#### 5.3 No Logging Framework
**Severity:** MEDIUM
**Issue:** Inconsistent logging (some `pub_test_print`, some `error_log`).
**Recommendation:** Implement PSR-3 logger (Monolog).

#### 5.4 No API Documentation
**Severity:** MEDIUM
**Issue:** No OpenAPI/Swagger documentation for endpoints.
**Recommendation:** Add API documentation.

#### 5.5 Inconsistent Code Style
**Severity:** LOW
**Issue:** Mixed formatting, inconsistent naming conventions.
**Recommendation:** Implement PHP CS Fixer, enforce PSR-12.

---

### 6. Configuration Management Issues

#### 6.1 Hardcoded Environment Detection
**Location:** Multiple files
```php
if (substr(__DIR__, 0, 2) == 'I:') { ... }
```
**Severity:** HIGH
**Issue:** Environment detection via drive letter is unreliable.
**Recommendation:** Use environment variables (`APP_ENV`, `APP_DEBUG`).

#### 6.2 Configuration Scattered Across Files
**Locations:**
- `src/bots/config.php` - OAuth config
- `src/bots/cors.php` - Allowed domains
- `src/bots/mdwiki_sql.php` - Database config
- `src/bots/files_helps.php` - Directory paths

**Severity:** MEDIUM
**Issue:** Configuration is scattered, making changes difficult.
**Recommendation:** Centralize configuration using a config library (e.g., vlucas/phpdotenv).

#### 6.3 Secrets in Code
**Location:** `src/bots/config.php:40-51`
**Severity:** CRITICAL
**Issue:** Encryption keys loaded from ini file but still present in code structure.
**Recommendation:** Use environment variables or secret management service.

---

### 7. External Dependencies Issues

#### 7.1 External File Dependencies
**Location:** `src/include.php:25-29`
```php
if (substr(__DIR__, 0, 2) == 'I:') {
    include_once 'I:/mdwiki/fix_refs_repo/work.php';
} else {
    include_once __DIR__ . '/../fix_refs/work.php';
}
```
**Severity:** HIGH
**Issue:** Depends on external repository that may not exist in production.
**Recommendation:** Package as Composer dependency or implement interface.

#### 7.2 Missing composer.json
**Severity:** MEDIUM
**Issue:** No composer.json for dependency management.
**Recommendation:** Create composer.json with proper autoloading.

---

## Refactoring Roadmap

### Phase 1: Critical Security Fixes (Week 1)
1. Re-enable CORS protection
2. Remove hardcoded credentials, use environment variables
3. Fix SQL injection vulnerabilities
4. Add input validation and sanitization
5. Implement proper error handling (no silent failures)

### Phase 2: Configuration Cleanup (Week 1-2)
1. Create `.env` file structure
2. Centralize all configuration
3. Remove environment detection via drive letters
4. Implement proper path resolution

### Phase 3: Reduce Global State (Week 2-3)
1. Create Service classes for globals
2. Implement Dependency Injection container
3. Refactor global variables to injected dependencies

### Phase 4: Database Layer Refactoring (Week 3-4)
1. Implement Repository pattern
2. Create Query Builder for safe queries
3. Add connection pooling/persistent connections
4. Fix inefficient queries

### Phase 5: Service Layer (Week 4-5)
1. Extract business logic from controllers
2. Create Service classes for:
   - PublishService
   - WikidataService
   - AccessService
   - ReportService

### Phase 6: Testing Infrastructure (Week 5-6)
1. Set up PHPUnit
2. Write unit tests for services
3. Write integration tests for API endpoints
4. Achieve 80%+ code coverage

### Phase 7: Code Quality Improvements (Week 6-7)
1. Add type hints throughout
2. Remove duplicate code
3. Implement PSR-12 coding standard
4. Add PHP CS Fixer and PHPStan

### Phase 8: Performance Optimization (Week 7-8)
1. Implement async processing for file operations
2. Add caching layer
3. Optimize database queries
4. Consider queue system for external API calls

---

## File-by-File Action Items

### `src/index.php`
- [ ] Re-enable CORS checking
- [ ] Add request validation middleware
- [ ] Implement proper error responses

### `src/include.php`
- [ ] Remove conditional includes
- [ ] Use autoloading instead
- [ ] Remove external `fix_refs` dependency or package properly

### `src/start.php`
- [ ] Extract to proper Controller class
- [ ] Remove direct $_POST access
- [ ] Add Request/Response objects

### `src/bots/config.php`
- [ ] Move to environment variables
- [ ] Remove hardcoded paths
- [ ] Implement Config class

### `src/bots/mdwiki_sql.php`
- [ ] Remove hardcoded credentials
- [ ] Implement Repository pattern
- [ ] Add query builder
- [ ] Fix SQL injection vulnerability

### `src/bots/helps.php`
- [ ] Remove global `$usr_agent`
- [ ] Implement proper logging
- [ ] Remove silent error catching

### `src/bots/access_helps.php` & `access_helps_new.php`
- [ ] Consolidate into single AccessRepository
- [ ] Implement caching properly
- [ ] Fix inefficient query

### `src/bots/process_edit.php`
- [ ] Extract to EditService
- [ ] Remove database calls from controller
- [ ] Implement proper error handling

### `src/bots/files_helps.php`
- [ ] Implement proper path validation
- [ ] Remove global state
- [ ] Consider async file operations

### `src/bots/wd.php`
- [ ] Extract to WikidataService
- [ ] Add proper error handling
- [ ] Implement retry logic

---

## Success Criteria

- [ ] All security vulnerabilities addressed
- [ ] No global variables (except configuration)
- [ ] 80%+ test coverage
- [ ] PSR-12 compliant codebase
- [ ] No hardcoded paths or credentials
- [ ] Clear separation of concerns
- [ ] API documentation available
- [ ] Production-ready error handling and logging

---

## Estimated Effort

- **Total Time:** 8 weeks
- **Critical Security Fixes:** 1 week
- **Configuration:** 1 week
- **Architecture Refactoring:** 4 weeks
- **Testing:** 2 weeks
- **Code Quality & Performance:** 2 weeks

---

## Notes

- Some changes may require coordination with the `fix_refs` repository
- Consider breaking changes for existing API consumers
- Database migrations may be needed for security improvements
- External Wikidata API changes should be monitored
