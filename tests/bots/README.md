# Test Suite for `src/bots/`

## Structure

```
tests/bots/
├── bootstrap.php               # Sets env vars; included by PHPUnit before any test
├── CorsTest.php                # Publish\CORS\is_allowed()
├── HelpsTest.php               # Publish\Helps\encode_value/decode_value/pub_test_print/get_url_curl
├── MdwikiSqlTest.php           # Publish\MdwikiSql\Database, get_dbname, execute_query, fetch_query
├── AccessHelpsTest.php         # Publish\AccessHelps + Publish\AccessHelpsNew (round-trip crypto)
├── AddToDbTest.php             # Publish\AddToDb\InsertPageTarget, InsertPublishReports
├── FilesHelpsAndRevidsTest.php # Publish\FilesHelps\to_do/check_dirs, Publish\Revids\get_revid
├── ProcessEditAndStartTest.php # Publish\EditProcess helpers + start.php pure-logic helpers
└── WdTest.php                  # Publish\WD URL/params construction, credential resolution
phpunit.xml                     # PHPUnit configuration
```

## Requirements

| Dependency            | Version              |
| --------------------- | -------------------- |
| PHP                   | ≥ 8.1                |
| phpunit/phpunit       | ^10                  |
| defuse/php-encryption | (already in project) |

Install PHPUnit (if not already present):

```bash
composer require --dev phpunit/phpunit ^10
```

## Running the Tests

```bash
# All unit tests (no network calls)
vendor/bin/phpunit tests/bots

# Verbose output
vendor/bin/phpunit tests/bots --testdox

# Single file
vendor/bin/phpunit tests/bots/HelpsTest.php

# Network-dependent tests (requires internet access)
vendor/bin/phpunit --group network
```

## Coverage Report

```bash
vendor/bin/phpunit --coverage-html coverage/
```

Open `coverage/index.html` in a browser.

## What Is (and Isn't) Covered

| File                   | Approach                       | Notes                                                |
| ---------------------- | ------------------------------ | ---------------------------------------------------- |
| `cors.php`             | Full unit (no I/O)             | 100% of function logic tested                        |
| `helps.php`            | Full unit (real Defuse crypto) | All branches including edge cases                    |
| `mdwiki_sql.php`       | SQLite shim via reflection     | `Database` class tested without MySQL                |
| `access_helps.php`     | Crypto round-trip + syntax     | DB layer requires MySQL for full integration         |
| `access_helps_new.php` | Crypto round-trip + syntax     | Same as above                                        |
| `add_to_db.php`        | Logic extraction + syntax      | DB INSERT/UPDATE need MySQL integration test         |
| `files_helps.php`      | Full (writes to `/tmp`)        | `check_dirs` and `to_do` fully exercised             |
| `revids_bot.php`       | Full unit (temp JSON files)    | `get_revid` fully covered; `get_revid_db` needs HTTP |
| `process_edit.php`     | Logic extraction + syntax      | OAuth/API calls require integration test             |
| `start.php`            | Logic extraction + syntax      | Cannot `include` directly (executes `start($_POST)`) |
| `wd.php`               | Logic extraction + syntax      | Wikidata API calls require integration test          |
| `get_token.php`        | Syntax only                    | OAuth calls require live credentials                 |
| `do_edit.php`          | Syntax only                    | OAuth + MediaWiki API needed                         |
| `config.php`           | Env-var validation             | Covered implicitly via bootstrap                     |

For full integration coverage, set `APP_ENV=testing` and point the DB env vars
at a test MySQL instance with the schema from your migration files.
