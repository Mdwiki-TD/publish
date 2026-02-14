<?php

declare(strict_types=1);

/**
 * Dependency loader for the MDWiki publishing system.
 *
 * This file includes all required dependencies for the publishing workflow:
 * - Vendor autoloader (OAuth client, encryption library)
 * - Database connectivity
 * - Configuration
 * - Helper functions
 * - OAuth access management
 * - Edit processing
 * - Wikidata integration
 * - External fix_refs repository
 *
 * @package Publish
 * @author  MDWiki Team
 * @since   1.0.0
 */

// Enable error reporting in test mode for debugging
// SECURITY NOTE: Debug mode should not be accessible in production without authentication
// @see ANALYSIS_REPORT.md SEC-012
if (isset($_REQUEST['test'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Load vendor dependencies (OAuth client, encryption)
include_once __DIR__ . '/vendor_load.php';

// Database connectivity
include_once __DIR__ . '/bots/mdwiki_sql.php';

// OAuth configuration
include_once __DIR__ . '/bots/config.php';

// Helper utilities (debugging, encryption, HTTP)
include_once __DIR__ . '/bots/helps.php';

// Revision ID lookup
include_once __DIR__ . '/bots/revids_bot.php';

// File-based logging helpers
include_once __DIR__ . '/bots/files_helps.php';

// Legacy OAuth access token management
include_once __DIR__ . '/bots/access_helps.php';

// New OAuth access token management
include_once __DIR__ . '/bots/access_helps_new.php';

// Wikipedia edit execution
include_once __DIR__ . '/bots/do_edit.php';

// Database recording for translations
include_once __DIR__ . '/bots/add_to_db.php';

// OAuth token retrieval
include_once __DIR__ . '/bots/get_token.php';

// Wikidata integration (disabled - textfixes module)
// include_once __DIR__ . '/textfixes/include.php';

// Wikidata sitelink operations
include_once __DIR__ . '/bots/wd.php';

// Edit process orchestration
include_once __DIR__ . '/bots/process_edit.php';

// External fix_refs repository for wikitext preprocessing
// Different paths for local development vs production
if (substr(__DIR__, 0, 2) === 'I:') {
    // Local Windows development path
    include_once 'I:/mdwiki/fix_refs_repo/work.php';
} else {
    // Production path (relative to this file)
    include_once __DIR__ . '/../fix_refs/work.php';
}
