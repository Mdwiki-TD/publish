# su/utils/ - Shared Utility Functions

## Project Overview

This module provides shared utility functions used by the publish workflow. It handles edit summary generation, title/user formatting, hashtag determination, error classification, and API parameter preparation.

### Purpose
- Generate edit summaries for Wikipedia edits
- Normalize titles and usernames
- Classify errors for report categorization
- Prepare API parameters for MediaWiki edit requests

### Files

| File | Namespace | Purpose |
|------|-----------|---------|
| `start_utils.php` | `Publish\StartUtils` | Core utility functions for the publish workflow |

## Architecture

### Functions

| Function | Purpose |
|----------|---------|
| `make_summary($revid, $sourcetitle, $to, $hashtag)` | Generates edit summary: `"Created by translating [[:mdwiki:Special:Redirect/revision/$revid\|$sourcetitle]] to:$to $hashtag"` |
| `formatTitle($title)` | Replaces underscores with spaces, normalizes `"Mr. Ibrahem 1/"` to `"Mr. Ibrahem/"` |
| `formatUser($user)` | Maps special usernames (`"Mr. Ibrahem 1"` -> `"Mr. Ibrahem"`, `"Admin"` -> `"Mr. Ibrahem"`), replaces underscores |
| `determineHashtag($title, $user)` | Returns `#mdwikicx` unless the article is a Mr. Ibrahem user page |
| `get_errors_file($editit, $place_holder)` | Classifies error responses into specific file name categories |
| `prepareApiParams($title, $summary, $text, $request)` | Builds MediaWiki edit API parameters |

### Error Classification (`get_errors_file`)
Categorizes errors into specific report file names:
- **Edit errors:** `protectedpage`, `titleblacklist`, `ratelimited`, `editconflict`, `spam filter`, `abusefilter`, `mwoauth-invalid-authorization`
- **Wikidata errors:** `wd_user_pages`, `wd_csrftoken`, `wd_protectedpage`

## Code Quality Review

### Strengths
1. **Centralized error classification** - `get_errors_file()` provides consistent error categorization across the application
2. **Simple, pure functions** - Most functions are stateless with no side effects
3. **Readable code** - Functions are short and self-explanatory
4. **Good edit summary format** - Includes source revision link and hashtag for traceability

### Weaknesses
1. **Hardcoded admin username** - `"Mr. Ibrahem"` appears in `formatUser()` and `determineHashtag()`
2. **Fragile error detection** - Uses `strpos()` on JSON-encoded strings to detect error types
3. **Special user mapping in multiple places** - `formatUser()` duplicates logic from `cxtoken/token_handler.php::handle_user_name()`
4. **`determineHashtag()` is overly specific** - Only suppresses hashtag for one specific user/title combination
5. **No return type declarations**

### Critical Issues
- **No input sanitization** - `formatTitle()` and `formatUser()` only do string replacement, no validation or sanitization of malicious input
- **`prepareApiParams()` passes through user input** - `$title`, `$summary`, `$text` from `$request` are included in API parameters without sanitization

## Areas That Need Attention
- Extract shared username normalization to avoid duplication
- Use structured error codes instead of string matching on JSON
- Add input validation/sanitization
- Add return type declarations
- Make hashtag configuration dynamic (not hardcoded)

## Improvement Plan
1. **Quick:** Add return types, extract shared username function
2. **Medium:** Add input validation, use structured error codes
3. **Long:** Create a `UserNormalizer` and `ErrorClassifier` service class

## Comprehensive Review

| Metric | Score |
|--------|-------|
| **Overall Rating** | 6/10 |
| **Security** | 5/10 - No input sanitization |
| **Maintainability** | 6/10 - Simple functions, but duplicated logic |
| **Code Quality** | 7/10 - Clean and readable |
