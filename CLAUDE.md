# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository manages the final steps of publishing Wikipedia articles translated using the ContentTranslation tool (mdwiki.toolforge.org). It takes translated wikitext, refines it using the [fix_refs](https://github.com/Mdwiki-TD/fix_refs) repository, and publishes to Wikipedia via OAuth.

## Development Commands

### Running the application locally
The main entry point is `src/index.php`. PHP is required. The application expects:
- A local web server pointing to `src/` directory
- Configuration files in `$HOME/confs/` (or `I:/mdwiki/mdwiki/confs/` for local development)

### Debug mode
Add `?test` query parameter to any request to enable verbose error reporting and debug output.

## Architecture

### Request Flow
1. `src/index.php` - Entry point, includes dependencies via `include.php`
2. `src/start.php` - Main `start()` function orchestrates the publish workflow:
   - Validates user access credentials from database
   - Gets revision ID for source article
   - Pre-processes wikitext via `fix_refs` (loaded from external path)
   - Submits edit to target Wikipedia via OAuth API
   - Links article to Wikidata on success
   - Logs results to database and JSON files

### Key Modules (src/bots/)

| File | Purpose |
|------|---------|
| `config.php` | OAuth configuration (consumer key/secret from INI file) |
| `mdwiki_sql.php` | PDO database wrapper with `fetch_query()` and `execute_query()` |
| `do_edit.php` | Makes OAuth-authenticated edit requests to Wikipedia API |
| `process_edit.php` | Orchestrates edit workflow, handles success/error cases |
| `wd.php` | Wikidata integration - links translated articles via `wbsetsitelink` |
| `access_helps.php` / `access_helps_new.php` | Retrieves encrypted OAuth tokens from database |
| `revids_bot.php` | Fetches revision IDs from JSON file or API |
| `add_to_db.php` | Inserts publish records to `pages`/`pages_users`/`publish_reports` tables |
| `files_helps.php` | Logs results to JSON files in `publish_reports/reports_by_day/` |

### Dependencies
- External `fix_refs` repository loaded from `I:/mdwiki/fix_refs_repo/work.php` (local) or `../fix_refs/work.php` (production)
- External `auth` repository for vendor autoloading via `vendor_load.php`
- OAuth client library (`mediawiki/oauthclient`)
- Encryption library (`defuse/php-encryption`) for token storage

### Database Tables
- `access_keys` - Stores encrypted OAuth tokens per user
- `pages` / `pages_users` - Tracks translation targets
- `publish_reports` - Logs all publish attempts with results
- `qids` - Maps titles to Wikidata QIDs

### Environment Detection
Code checks if `__DIR__` starts with `I:` to detect local Windows development vs Toolforge production. Different paths are used for config files and external dependencies.

## Report Viewing
- `src/publish_reports/index.php` - Web UI to browse JSON reports by year/month/day
- `src/reports.php` - DataTables-based viewer querying `publish_reports` database table
