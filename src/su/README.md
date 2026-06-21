# su/ - Core Application Module

## Project Overview

`su/` (short for "Start Up") is the core application module containing all business logic for the MDWiki Publish API. It orchestrates the entire publish workflow from request validation through Wikipedia API calls to result logging.

### Purpose
- Central include/bootstrap system for all application modules
- OAuth consumer configuration
- CORS domain validation
- Environment variable loading (development mode)
- Wikitext preprocessing via external `fix_refs` library

### Technologies
- PHP 8.0+ with PSR-4 namespaced functions
- `mediawiki/oauthclient` for OAuth 1.0a
- `defuse/php-encryption` for token encryption
- PDO for MySQL/MariaDB
- cURL for HTTP requests

## Project Structure

```
su/
├── include.php        # Master bootstrap - loads all modules and external dependencies
├── config.php         # OAuth consumer key/secret from environment
├── cors.php           # CORS domain validation (referer/origin checking)
├── load_env.php       # Development environment variables (WARNING: contains credentials)
├── text_edit.php      # Wikitext preprocessing via fix_refs integration
├── api/               # Wikipedia API interaction layer
│   ├── do_edit.php    # OAuth-authenticated edit execution
│   └── wiki_api.php   # Title/page information queries
├── bots/              # Utility/helper modules
│   ├── index.php      # Module loader
│   ├── curl_requests.php  # HTTP client
│   ├── crypt_helps.php    # Encryption/decryption
│   ├── files_helps.php    # Report file logging
│   ├── helps.php          # Debug output helper
│   ├── revids_bot.php     # Revision ID lookup
│   └── wd.php             # Wikidata integration
├── cxtoken/           # ContentTranslation token management
│   ├── get_token.php      # CX token retrieval from Wikipedia
│   └── token_handler.php  # Token orchestration with error handling
├── mw_client/         # MediaWiki OAuth client wrapper
│   └── index.php      # OAuth client, tokens, CSRF, POST operations
├── process/           # Edit processing & orchestration
│   ├── start.php          # Main workflow orchestrator
│   ├── process_edit.php   # Edit execution and post-edit handling
│   └── process_db_log.php # Database insertion logic
├── sql/               # Database access layer
│   ├── mdwiki_sql.php     # PDO Database class and query wrappers
│   ├── access_helps.php   # OAuth token CRUD
│   ├── add_to_db.php      # Publish report and page tracking inserts
│   ├── sql.php            # General queries (QID, categories, upsert)
│   └── table_name.php     # Placeholder/empty namespace
└── utils/             # Shared utility functions
    └── start_utils.php    # Summary generation, formatting, error classification
```

## Architecture

### Module Dependency Graph
```
include.php (bootstrap)
  ├── vendor_load.php → Composer autoloader
  ├── config.php → OAuth credentials
  ├── cors.php → Domain validation
  ├── text_edit.php → fix_refs integration
  ├── utils/start_utils.php → Formatting, error classification
  ├── process/
  │   ├── start.php → Main orchestrator
  │   ├── process_edit.php → Edit handler
  │   └── process_db_log.php → DB logging
  ├── sql/
  │   ├── mdwiki_sql.php → Database class
  │   ├── access_helps.php → Token storage
  │   ├── add_to_db.php → Record insertion
  │   └── sql.php → General queries
  ├── mw_client/index.php → OAuth client
  ├── api/
  │   ├── do_edit.php → Edit execution
  │   └── wiki_api.php → Title queries
  ├── bots/
  │   ├── curl_requests.php → HTTP client
  │   ├── crypt_helps.php → Encryption
  │   ├── files_helps.php → File logging
  │   ├── helps.php → Debug helper
  │   ├── revids_bot.php → Revision lookup
  │   └── wd.php → Wikidata linking
  └── cxtoken/
      ├── get_token.php → CX token fetch
      └── token_handler.php → Token orchestration
```

### Request Processing Pipeline
```
1. Entry point (index.php/token.php)
2. CORS validation (cors.php)
3. Secret key verification (index.php)
4. Bootstrap all modules (include.php)
5. User access validation (sql/access_helps.php)
6. Revision ID lookup (bots/revids_bot.php)
7. Wikitext preprocessing (text_edit.php → fix_refs)
8. Edit execution (api/do_edit.php)
9. Post-edit operations (process/process_edit.php)
   - Wikidata linking (bots/wd.php)
   - Database logging (sql/add_to_db.php)
   - File logging (bots/files_helps.php)
10. JSON response
```

## Code Quality Review

### Design Patterns
- **Bootstrap/Include pattern** - `include.php` loads all dependencies manually
- **Procedural with namespaces** - Functions organized under `Publish\*` namespaces
- **Gateway pattern** - `Database` class wraps PDO
- **Pipeline pattern** - Request flows through sequential processing stages

### SOLID Compliance
- **SRP:** Partial - each file has a focused purpose, but orchestration functions mix concerns
- **OCP/LSP/ISP:** Not applicable (procedural architecture)
- **DIP:** Not followed - functions directly instantiate dependencies

### Naming Conventions
- Namespaces: `Publish\*` (PSR-4 compliant)
- Functions: Mixed `camelCase` and `snake_case` (inconsistent)
- Database methods: `executequery`/`fetchquery` (no underscores, inconsistent with wrapper functions)

## Strengths

1. **Clean module separation** - Each subdirectory has a clear domain responsibility
2. **Parameterized SQL** - All database queries use prepared statements
3. **Encrypted credentials** - OAuth tokens encrypted at rest with `defuse/php-encryption`
4. **Comprehensive logging** - Dual logging to database and JSON files
5. **Environment detection** - Automatic development/production path switching
6. **External dependency isolation** - `fix_refs` loaded from configurable path

## Weaknesses

1. **Manual dependency management** - `include_once` chains instead of autoloader
2. **No dependency injection** - Functions create their own dependencies
3. **Inconsistent naming** - Mixed `camelCase`/`snake_case`, `executequery` vs `fetch_query`
4. **Global state** - `crypt_helps.php` uses `global $decrypt_key`
5. **Hardcoded paths** - Windows development paths in production code
6. **Duplicated logic** - Username normalization in `start_utils.php` and `token_handler.php`

## Critical Issues

> **WARNING: These issues require immediate attention.**

| Issue | Location | Risk |
|-------|----------|------|
| Hardcoded DB credentials and encryption keys | `load_env.php` | **Critical** |
| CORS bypass via substring matching | `cors.php` | **High** |
| Empty secret code bypasses auth | `index.php` | **High** |
| SQL query exposure in debug mode | `mdwiki_sql.php` | **Medium** |
| No input validation on POST data | `process/start.php` | **Medium** |
| Hardcoded admin username | Multiple files | **Medium** |
| File path injection risk | `bots/files_helps.php` | **Medium** |
| Hardcoded Windows paths | `files_helps.php`, `start.php` | **Low** |

## Areas That Need Attention
- **Security:** Remove `load_env.php` from version control, add to `.gitignore`
- **Architecture:** Replace `include_once` chains with Composer autoloading
- **Input validation:** Add centralized validation for all POST parameters
- **Testing:** Increase unit test coverage for `process/` module
- **Configuration:** Extract hardcoded values to environment variables
- **Documentation:** Add inline documentation for complex functions

## Improvement Plan

### Quick Fixes (1-2 days)
1. Add `load_env.php` to `.gitignore`, rotate all exposed credentials
2. Fix CORS validation to use `parse_url()` for exact host matching
3. Return 403 when `PUBLISH_SECRET_CODE` is empty
4. Remove hardcoded Windows paths
5. Add return type declarations to all functions

### Medium-term (1-2 weeks)
1. Replace `include_once` chains with Composer PSR-4 autoloading
2. Add centralized input validation middleware
3. Extract shared username normalization utility
4. Implement connection pooling for database
5. Add request/response logging middleware

### Long-term (1-2 months)
1. Refactor to class-based architecture with dependency injection
2. Add comprehensive unit tests (target 80%+ coverage)
3. Implement caching for QID lookups and word counts
4. Add CI/CD pipeline with PHPStan, PHPUnit, and security scanning
5. Create OpenAPI documentation

## Comprehensive Review

| Metric | Score | Notes |
|--------|-------|-------|
| **Overall Rating** | 5/10 | Functional but needs hardening |
| **Production Readiness** | 5/10 | Works but has security gaps |
| **Security Score** | 4/10 | Credential exposure, CORS bypass |
| **Technical Debt** | 6/10 | Procedural, inconsistent naming |
| **Maintainability** | 5/10 | Readable but tightly coupled |
| **Test Coverage** | 5/10 | Tests exist but gaps in core modules |
| **Risk Assessment** | Medium-High | Credential exposure is critical |

## Environment Variables

| Variable | Purpose | Required |
|----------|---------|----------|
| `APP_ENV` | Environment (`development`/`production`) | No (defaults to `development`) |
| `DB_HOST_TOOLS` | Database host | Yes |
| `DB_NAME` | Database name | Yes |
| `TOOL_TOOLSDB_USER` | Database username | Yes |
| `TOOL_TOOLSDB_PASSWORD` | Database password | Yes |
| `CONSUMER_KEY` | MediaWiki OAuth consumer key | Yes |
| `CONSUMER_SECRET` | MediaWiki OAuth consumer secret | Yes |
| `DECRYPT_KEY` | Defuse encryption key for token storage | Yes |
| `PUBLISH_SECRET_CODE` | API secret key for endpoint auth | No (bypasses auth if empty) |
| `PUBLISH_REPORTS_PATH` | Path for JSON report files | No (has defaults) |
| `ALL_PAGES_REVIDS_PATH` | Path to `all_pages_revids.json` | No (has defaults) |
| `TEXT_WORK_FILE` | Path to `fix_refs/work.php` | No (has defaults) |
| `WIKIDATA_DOMAIN` | Wikidata API domain | No (defaults to `www.wikidata.org`) |
