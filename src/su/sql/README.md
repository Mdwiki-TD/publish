# su/sql/ - Database Access Layer

## Project Overview

This module provides the database access layer for the application. It wraps PDO for MySQL/MariaDB connections and provides functions for querying and modifying the database, managing OAuth access keys, and tracking published articles.

### Purpose
- PDO database connection management with singleton-like pattern
- Parameterized query execution (preventing SQL injection)
- OAuth token storage and retrieval (encrypted)
- Publish report logging
- Page/article tracking in `pages` and `pages_users` tables

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `mdwiki_sql.php` | `Publish\MdwikiSql` | PDO `Database` class and `execute_query()`/`fetch_query()` wrappers |
| `access_helps.php` | `Publish\AccessHelps` | OAuth token CRUD operations (get/delete from `access_keys` table) |
| `add_to_db.php` | `Publish\AddToDb` | Insert publish reports and page target records |
| `sql.php` | `Publish\Sql` | General SQL queries (QID lookup, campaign categories, upsert logic) |
| `table_name.php` | `Publish\MdwikiSql` | Empty namespace declaration (placeholder) |

## Architecture

### `Database` Class (`mdwiki_sql.php`)
The central database abstraction:

```
Database
  ├── __construct($dbname_var)  // Reads credentials from environment
  ├── executequery($sql, $params)  // Execute INSERT/UPDATE/DELETE
  ├── fetchquery($sql, $params)    // Execute SELECT, return array
  ├── disableFullGroupByMode()     // MySQL ONLY_FULL_GROUP_BY workaround
  └── __destruct()                 // Close connection
```

### Helper Functions
```php
execute_query($sql, $params, $table_name)  // Creates Database, executes, destroys
fetch_query($sql, $params, $table_name)    // Creates Database, fetches, destroys
```

### Table Schema (inferred)
- **`access_keys`** - `user_name`, `user_name_hash`, `access_key`, `access_secret`
- **`publish_reports`** - `date`, `title`, `user`, `lang`, `sourcetitle`, `result`, `data`
- **`pages`** / **`pages_users`** - `title`, `word`, `translate_type`, `cat`, `lang`, `user`, `pupdate`, `target`, `mdwiki_revid`
- **`qids`** - `title`, `qid`
- **`categories`** - `category`, `campaign`
- **`language_settings`** - `lang_code`, `move_dots`, `expend`, `add_en_lang`

## Code Quality Review

### Strengths
1. **Parameterized queries** - All queries use PDO prepared statements with `?` placeholders
2. **Table name allowlisting** - `find_exists_or_update()` and `InsertPageTarget()` validate table names against a whitelist
3. **Encrypted token storage** - OAuth tokens stored encrypted, decrypted on read
4. **Hash-based user lookup** - Supports lookup by both `user_name` and `user_name_hash` (SHA-256)
5. **SQL mode management** - `disableFullGroupByMode()` handles MySQL strict mode

### Weaknesses
1. **Connection per query** - `execute_query()` and `fetch_query()` create a new `Database` instance per call (no connection reuse)
2. **Empty `$table_name` parameter** - Both helper functions accept `$table_name` but never use it (dead parameter)
3. **`sql.php` is nearly empty** - Only contains a namespace declaration
4. **Silent error swallowing** - Database errors return empty arrays instead of throwing exceptions
5. **Inconsistent method naming** - `executequery`/`fetchquery` (no underscores) vs `execute_query`/`fetch_query` (with underscores)
6. **`ONLY_FULL_GROUP_BY` hack** - Disabling SQL mode globally per connection is a workaround, not a fix

### Critical Issues

> **WARNING**

1. **Database credentials in connection string** (`mdwiki_sql.php:47`):
   ```php
   $this->db = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->password);
   ```
   If credentials contain special characters, this will fail or behave unexpectedly. Use DSN parameter array.

2. **Error messages exposed to client** (`mdwiki_sql.php:54`):
   ```php
   echo "Unable to connect to the database. Please try again later.";
   ```
   While this specific message is safe, `pub_test_print()` in catch blocks exposes SQL queries and error messages in debug mode.

3. **No connection timeout** - PDO connection has no timeout configured, which can cause requests to hang indefinitely.

4. **SQL query in error output** (`mdwiki_sql.php:96`):
   ```php
   pub_test_print("sql error:" . $e->getMessage() . "<br>" . $sql_query);
   ```
   Exposes full SQL queries to clients when `?test` parameter is present.

## Areas That Need Attention
- Implement connection pooling or singleton pattern for `Database`
- Remove dead `$table_name` parameter from helper functions
- Add PDO connection timeout and charset configuration
- Use DSN parameter array for credential safety
- Remove SQL query exposure in debug output
- Add proper exception handling (throw instead of silent return)
- Rename methods for consistency (`executequery` -> `executeQuery`)

## Improvement Plan
1. **Quick:** Add PDO timeout/charset, remove dead parameters, rename methods consistently
2. **Medium:** Implement connection singleton, add proper exception handling, remove debug SQL exposure
3. **Long:** Create a `Repository` pattern with typed query results, add connection pooling

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 5/10 |
| **Security** | 5/10 - Prepared statements good, but SQL exposure in debug mode |
| **Performance** | 4/10 - New connection per query, no pooling |
| **Maintainability** | 5/10 - Inconsistent naming, dead code |
| **Reliability** | 5/10 - Silent error swallowing hides problems |
