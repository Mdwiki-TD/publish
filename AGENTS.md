# AGENTS.md

This file provides guidance to agentic coding assistants working in this repository.

## Project Overview

PHP application for publishing Wikipedia articles translated via ContentTranslation tool (mdwiki.toolforge.org). Processes wikitext, refines via external `fix_refs` repo, and publishes via OAuth API.

## Build/Test Commands

```bash
# Install dependencies
composer install

# Run all tests
composer test
# OR
vendor/bin/phpunit tests --testdox --colors=always -c phpunit.xml

# Run a single test file
vendor/bin/phpunit tests/HelpsTest.php

# Run a single test method
vendor/bin/phpunit --filter testPubTestPrintHandlesArrays tests/HelpsTest.php

# Run static analysis
vendor/bin/phpstan analyse src --level=5
```

## Code Style Guidelines

### File Structure
- PHP opening tag: `<?php` (lowercase) or `<?PHP` for entry points
- Declare strict types at top: `declare(strict_types=1);`
- Files in `src/bots/` are modules; `src/index.php` is entry point

### Namespaces & Imports
- Use namespaces: `namespace Publish\ModuleName;`
- Group imports: `use` statements after namespace, before code
- Import functions: `use function Publish\ModuleName\functionName;`
- Namespace naming: PascalCase matching directory structure

### Naming Conventions
- Functions: snake_case (e.g., `publish_do_edit()`, `get_errors_file()`)
- Classes: PascalCase (when used)
- Variables: snake_case (e.g., `$access_key`, `$api_params`)
- Constants: UPPER_CASE (e.g., `$CONSUMER_KEY`)
- Files: snake_case.php

### Formatting
- Indentation: 4 spaces
- Brace style: K&R (opening brace on same line for functions)
- Comments: `// ---` as section separators (common in this codebase)
- Array syntax: short `[]` preferred
- String quotes: single quotes for literals, double for interpolation

### Error Handling
- Use try/catch for crypto operations and external APIs
- Return null or empty arrays on failure, not exceptions (for API calls)
- Log errors via `pub_test_print()` for debug output
- Check `isset()` before accessing array/object keys

### Type Safety
- Prefer type hints: `function foo(string $bar): array`
- Use `declare(strict_types=1);` in new files
- Document complex return types in comments

### Security
- Never log or expose OAuth tokens, keys, or secrets
- Use `getenv()` for configuration, never hardcode credentials
- Validate all user inputs before processing
- Use prepared statements for database queries

### Testing
- Test files in `tests/` directory with `Test.php` suffix
- Extend `PHPUnit\Framework\TestCase`
- Use `declare(strict_types=1);` in all test files
- Mock database connections; don't load `include.php` in tests
- Backup/restore `$_REQUEST` when testing request-dependent code

## Architecture Notes

### Key Patterns
- Include guards: `include_once __DIR__ . '/file.php';`
- Module structure: namespace + usage comment + includes + use statements + functions
- Environment detection: Check `__DIR__` for `I:` prefix (local Windows vs Toolforge)

### External Dependencies
- `fix_refs` repo: `I:/mdwiki/fix_refs_repo/work.php` (local) or `../fix_refs/work.php` (prod)
- Config files: `$HOME/confs/` (prod) or `I:/mdwiki/mdwiki/confs/` (local)
- OAuth via `mediawiki/oauthclient`
- Encryption via `defuse/php-encryption`

### Debug Mode
Add `?test` query parameter to enable verbose output via `pub_test_print()`

## Database
- PDO wrapper in `src/bots/mdwiki_sql.php`
- Tables: `access_keys`, `pages`, `pages_users`, `publish_reports`, `qids`
- Test environment uses mock credentials in `tests/bootstrap.php`
