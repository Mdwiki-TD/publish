# src/ - Application Source Root

## Project Overview

This directory contains the core source code for the **MDWiki Publish API** - a PHP application that publishes Wikipedia articles translated via the [ContentTranslation tool](https://github.com/mdwikicx/cx-1) on [mdwiki.toolforge.org](https://mdwiki.toolforge.org). It receives translated wikitext via POST requests, preprocesses it using the external [fix_refs](https://github.com/Mdwiki-TD/fix_refs) library, and submits edits to Wikipedia through OAuth-authenticated API calls.

### Main Features
- Publishes translated Wikipedia articles via MediaWiki OAuth API
- Preprocesses wikitext (fixes references, expands infoboxes, adds categories)
- Links published articles to Wikidata via `wbsetsitelink`
- Retrieves and manages CX (ContentTranslation) tokens
- Logs all publish attempts to database and JSON report files
- CORS-restricted token endpoint for frontend integration

### Technologies
- **Language:** PHP 8.0+
- **Framework:** None (procedural/functional architecture with PSR-4 namespaces)
- **Database:** MySQL/MariaDB (PDO)
- **External Libraries:**
  - `mediawiki/oauthclient` ^1.2 - MediaWiki OAuth 1.0a client
  - `defuse/php-encryption` ^2.4 - Symmetric encryption for token storage
  - `firebase/php-jwt` 7.0.0 - JWT handling
- **Testing:** PHPUnit 10, PHPStan level 5

## Project Structure

```
src/
├── index.php          # Main entry point - POST API for publishing articles
├── main.php           # Legacy redirect to index.php
├── token.php          # GET API endpoint for retrieving CX tokens
├── vendor_load.php    # Composer autoloader resolution (multi-path fallback)
├── pup2.html          # Static HTML page (publish UI)
├── reports.html       # Static HTML page (reports viewer)
└── su/                # Core application module ("Start Up")
    ├── include.php    # Master include file - loads all modules
    ├── config.php     # OAuth consumer key/secret from environment
    ├── cors.php       # CORS domain validation
    ├── load_env.php   # Development environment variables
    ├── text_edit.php  # Wikitext preprocessing via fix_refs
    ├── api/           # Wikipedia API interaction layer
    ├── bots/          # Utility/helper modules
    ├── cxtoken/       # ContentTranslation token management
    ├── mw_client/     # MediaWiki OAuth client wrapper
    ├── process/       # Edit orchestration and processing
    ├── sql/           # Database access layer
    └── utils/         # Shared utility functions
```

### Entry Points

| File | Method | Purpose |
|------|--------|---------|
| `index.php` | POST | Main publish API - receives translated wikitext and publishes to Wikipedia |
| `token.php` | GET | Returns CX tokens for authenticated users (CORS-protected) |
| `main.php` | GET | Legacy redirect to `index.php` |

### Request Flow

```
POST /index.php
  → CORS/secret key validation
  → su/process/start.php::start()
    → Validate user access (database lookup)
    → Get source revision ID
    → Preprocess wikitext (fix_refs)
    → su/process/process_edit.php::processEdit()
      → su/api/do_edit.php::publish_do_edit() (OAuth edit to Wikipedia)
      → On success: su/bots/wd.php::LinkToWikidata()
      → Log to database and JSON files
    → Return JSON response
```

## Architecture & Code Quality Review

### Code Organization
The codebase uses a **functional/procedural architecture** with PSR-4 namespaced functions (not classes). Each module file defines a namespace and exports standalone functions. The `include.php` file acts as a manual dependency injection container, loading all modules via `include_once`.

### Design Patterns
- **Procedural with namespaces** - Functions are organized by domain concern under `Publish\*` namespaces
- **Gateway pattern** - `Database` class wraps PDO for SQL operations
- **Facade pattern** - `include.php` provides a single entry to load all dependencies

### SOLID Principles Compliance
- **SRP:** Partially followed - each file has a focused purpose, but some functions mix concerns (e.g., `processEdit` handles editing, Wikidata linking, DB logging, and file logging)
- **OCP:** Not applicable (procedural code, no inheritance)
- **LSP:** Not applicable
- **ISP:** Not applicable
- **DIP:** Not followed - functions directly instantiate `Database` objects and call global functions

### Maintainability
- Moderate - code is readable but tightly coupled through global function calls
- No dependency injection framework; dependencies are resolved via `include_once` chains
- Naming conventions are inconsistent (`fetchquery` vs `fetch_query`, `executequery` vs `execute_query`)

### Scalability
- Each database call creates a new `Database` instance (no connection pooling)
- File-based logging creates directory trees per request (potential I/O bottleneck under load)
- No caching layer for frequently accessed data (e.g., word counts, QIDs)

## Strengths

1. **Clear domain separation** - Modules are organized by concern (API, SQL, bots, process)
2. **Parameterized SQL queries** - All database queries use prepared statements, preventing SQL injection
3. **Encrypted token storage** - OAuth tokens are encrypted at rest using `defuse/php-encryption`
4. **Comprehensive error logging** - Failed edits are categorized and logged to both database and JSON files
5. **Fallback mechanisms** - Wikidata linking retries with admin credentials on CSRF failure
6. **Environment-based configuration** - Secrets loaded from environment variables (production)
7. **Test infrastructure** - PHPUnit and PHPStan configured with unit and integration tests

## Weaknesses

1. **No autoloading for application code** - Uses `include_once` chains instead of Composer PSR-4 autoloading (despite having it configured)
2. **Global state dependency** - `crypt_helps.php` uses `global $decrypt_key` instead of dependency injection
3. **Inconsistent naming** - Mixed `camelCase` and `snake_case` (`fetchquery` vs `fetch_query`, `executequery` vs `execute_query`)
4. **Duplicate function logic** - `fetchquery` and `executequery` in the `Database` class are nearly identical
5. **Hardcoded fallback paths** - Windows paths like `I:/MD_TOOLS/...` appear in production code paths
6. **No input validation layer** - Input sanitization is scattered across individual functions
7. **Tight coupling** - `processEdit()` directly calls Wikidata, database, and file logging functions

## Critical Issues

> **WARNING: The following issues require immediate attention.**

### 1. Hardcoded Credentials in `load_env.php`
```php
// src/su/load_env.php
putenv('TOOL_TOOLSDB_USER=root');
putenv('TOOL_TOOLSDB_PASSWORD=root11');
putenv('COOKIE_KEY=def000008f0992f...');
putenv('DECRYPT_KEY=def000001358577e...');
```
**Risk:** High - Database credentials and encryption keys are committed to version control.
**Fix:** Remove `load_env.php` from version control, add it to `.gitignore`, and use a `.env` file with a `.env.example` template.

### 2. CORS Validation Bypass via `strpos`
```php
// src/su/cors.php
if (strpos($referer, $domain) !== false || strpos($origin, $domain) !== false)
```
**Risk:** Medium - An attacker can bypass CORS by using a referer like `https://evil.com/?medwiki.toolforge.org`.
**Fix:** Use `parse_url()` to extract the host and compare it exactly.

### 3. Secret Key Timing Attack
```php
// src/index.php
if (hash_equals($publish_secret_code, $received_key))
```
Good: Uses `hash_equals()` for timing-safe comparison. However, when `PUBLISH_SECRET_CODE` is empty, the check returns `true` (bypasses auth entirely).

### 4. SQL Injection via Table Name
```php
// src/su/sql/sql.php
$query = "SELECT * FROM $table_name WHERE title = ? AND lang = ? AND user = ?";
```
**Risk:** Low-Medium - While there's an allowlist check, the table name is interpolated into SQL. The allowlist mitigates this, but it's an unsafe pattern.

### 5. Error Message Disclosure
```php
// src/su/sql/mdwiki_sql.php
echo "Unable to connect to the database. Please try again later.";
pub_test_print("sql error:" . $e->getMessage() . "<br>" . $sql_query);
```
**Risk:** Medium - Database error messages and queries are exposed to clients in debug mode.

## Areas That Need Attention

- **Missing input validation** - No centralized validation for POST parameters (`title`, `user`, `target`, `text`)
- **Missing rate limiting** - No protection against abuse of the publish endpoint
- **Hardcoded admin username** - `"Mr. Ibrahem"` is hardcoded as fallback user in multiple files
- **No request logging** - No middleware to log incoming requests for audit trails
- **Outdated patterns** - `include_once` chains should be replaced with Composer autoloading
- **Missing `.gitignore` entry** for `load_env.php` with real credentials
- **No HTTPS enforcement** - No redirect from HTTP to HTTPS
- **Test coverage gaps** - Integration tests exist but core `process/start.php` has limited unit test coverage

## Improvement Plan

### Quick Fixes (1-2 days)
1. Add `load_env.php` to `.gitignore` and rotate all exposed credentials
2. Fix CORS validation to use `parse_url()` for exact host matching
3. Return 403 when `PUBLISH_SECRET_CODE` is empty (don't bypass auth)
4. Remove hardcoded Windows paths from `files_helps.php` and `start.php`

### Medium-term (1-2 weeks)
1. Replace `include_once` chains with Composer PSR-4 autoloading
2. Introduce a simple dependency injection container
3. Add centralized input validation middleware
4. Add request/response logging for audit trails
5. Standardize naming conventions across the codebase
6. Add rate limiting to the publish endpoint

### Long-term (1-2 months)
1. Refactor to class-based architecture with proper DI
2. Add comprehensive unit tests (target 80%+ coverage)
3. Implement caching for QID lookups and word counts
4. Add CI/CD pipeline with PHPStan, PHPUnit, and security checks
5. Create OpenAPI/Swagger documentation for the API

## Comprehensive Review

| Metric | Score | Notes |
|--------|-------|-------|
| **Overall Rating** | 5/10 | Functional but needs significant hardening |
| **Production Readiness** | 5/10 | Works in production but has security gaps |
| **Security Score** | 4/10 | Hardcoded credentials, CORS bypass, no rate limiting |
| **Technical Debt** | 6/10 | Procedural style, inconsistent naming, tight coupling |
| **Maintainability** | 5/10 | Readable but hard to extend without side effects |
| **Risk Assessment** | Medium-High | Credential exposure is the highest risk |

## Setup & Usage

### Prerequisites
- PHP 8.0+
- Composer
- MySQL/MariaDB database access
- MediaWiki OAuth consumer credentials

### Installation
```bash
cd src/
composer install
```

### Environment Variables
```env
APP_ENV=production
DB_HOST_TOOLS=tools.db.svc.wikimedia.cloud
DB_NAME=your_database_name
TOOL_TOOLSDB_USER=your_db_user
TOOL_TOOLSDB_PASSWORD=your_db_password
CONSUMER_KEY=your_mediawiki_oauth_consumer_key
CONSUMER_SECRET=your_mediawiki_oauth_consumer_secret
PUBLISH_SECRET_CODE=your_api_secret_key
DECRYPT_KEY=your_defuse_encryption_key
PUBLISH_REPORTS_PATH=/path/to/reports
ALL_PAGES_REVIDS_PATH=/path/to/all_pages_revids.json
TEXT_WORK_FILE=/path/to/fix_refs/work.php
```

### Running Locally
```bash
# Start PHP built-in server
php -S localhost:8000 -t src/

# Test publish endpoint
curl -X POST http://localhost:8000/index.php \
  -d "title=TestArticle" \
  -d "user=TestUser" \
  -d "target=es" \
  -d "text=Hello world" \
  -d "sourcetitle=Test_Article"

# Enable debug mode
curl -X POST "http://localhost:8000/index.php?test" \
  -d "title=TestArticle" \
  -d "user=TestUser" \
  -d "target=es" \
  -d "text=Hello world"
```

### Running Tests
```bash
composer test                    # Run PHPUnit tests
vendor/bin/phpstan analyse       # Static analysis
```
