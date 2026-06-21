# su/process/ - Edit Processing & Orchestration

## Project Overview

This module is the core orchestration layer of the publish workflow. It coordinates the entire process of publishing a translated article: validating access, preprocessing text, executing the edit, linking to Wikidata, and logging results.

### Purpose
- Main `start()` function that orchestrates the full publish workflow
- Process edit requests and handle success/failure cases
- Manage database logging of publish results

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `start.php` | `Publish\Start` | Main entry point - orchestrates the full publish workflow |
| `process_edit.php` | `Publish\EditProcess` | Executes edits and handles post-edit operations (Wikidata, DB logging) |
| `process_db_log.php` | `Publish\EditProcess` | Database insertion logic for page tracking |

## Architecture

### `start.php` - Main Orchestrator
The `start($request)` function is the heart of the application:

```
start($request)
  ├─ Validate user access (get_access_from_db)
  ├─ Load word count table
  ├─ Get source revision ID (JSON file → API fallback)
  ├─ Generate edit summary
  ├─ Preprocess wikitext (fix_refs)
  ├─ processEdit() ─────────────────────────┐
  │   ├─ prepareApiParams()                  │
  │   ├─ publish_do_edit() → Wikipedia API   │
  │   ├─ On success:                         │
  │   │   ├─ handleSuccessfulEdit()          │
  │   │   │   ├─ LinkToWikidata()            │
  │   │   │   └─ Retry with admin on failure │
  │   │   └─ add_to_db()                     │
  │   ├─ Log to JSON files                   │
  │   └─ Log to database                     │
  └─ Return JSON response
```

### `process_edit.php` - Edit Handler
Contains three key functions:
- **`processEdit()`** - Main edit execution with full error handling
- **`handleSuccessfulEdit()`** - Wikidata linking with admin fallback
- **`retryWithFallbackUser()`** - Retries Wikidata linking with admin credentials

### `process_db_log.php` - Database Logging
Contains `add_to_db()` which:
- Determines if the article belongs in `pages` or `pages_users` table
- Checks if the record already exists
- Inserts new records or updates existing ones

## Code Quality Review

### Strengths
- **Comprehensive error categorization** - Errors are classified into specific types (protectedpage, titleblacklist, ratelimited, etc.)
- **Fallback mechanism** - Wikidata linking retries with admin credentials on CSRF failure
- **Detailed logging** - Results logged to both database and date-organized JSON files
- **Captcha handling** - Detects and categorizes captcha challenges separately
- **Random request IDs** - Uses `time() . bin2hex(random_bytes(6))` for traceability

### Weaknesses
1. **God function** - `processEdit()` handles editing, Wikidata linking, database logging, and file logging
2. **Hardcoded admin username** - `"Mr. Ibrahem"` appears in multiple places as fallback user
3. **Abusefilter detection via string search** - `strpos(json_encode($linktowikidata), "abusefilter-warning-39")` is fragile
4. **Mixed concerns in `start()`** - Loads word table, generates summary, preprocesses text, and calls edit
5. **No return type declarations** on any function

### Critical Issues

> **WARNING**

1. **Hardcoded admin credentials fallback** (`process_edit.php:41`):
   ```php
   $fallback_access = get_access_from_db('Mr. Ibrahem');
   ```
   The admin username is hardcoded. If this account is compromised, all Wikidata linking operations are affected.

2. **File path in `load_words_table()`** (`start.php:21-23`):
   ```php
   $word_file = __DIR__ . "/../../td/Tables/jsons/words.json";
   if (!file_exists($word_file)) {
       $word_file = "I:/MD_TOOLS/MDWIKI_MAIN_REPO/public_html/td/Tables/jsons/words.json";
   }
   ```
   Hardcoded Windows development path leaks into production code.

3. **No input validation** - `$request` parameters (`title`, `user`, `target`, `text`) are used without sanitization before being passed to multiple subsystems.

## Areas That Need Attention
- Break `processEdit()` into smaller, focused functions
- Extract admin username to configuration
- Add input validation at the `start()` entry point
- Replace string-based error detection with structured error codes
- Remove hardcoded Windows paths
- Add return type declarations

## Improvement Plan
1. **Quick:** Extract admin username to env var, remove Windows paths, add return types
2. **Medium:** Split `processEdit()` into sub-functions, add input validation
3. **Long:** Create a `PublishWorkflow` class with injected dependencies, event-driven logging

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 5/10 |
| **Security** | 4/10 - No input validation, hardcoded admin |
| **Maintainability** | 4/10 - God functions, tight coupling |
| **Reliability** | 6/10 - Good fallback patterns |
| **Testability** | 4/10 - Tightly coupled to database and file system |
