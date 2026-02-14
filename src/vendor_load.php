<?php

declare(strict_types=1);

/**
 * Vendor autoloader loader for external dependencies.
 *
 * This file loads the Composer autoloader from the external auth repository,
 * which provides:
 * - MediaWiki OAuth client (addwiki/oauth-client)
 * - Defuse PHP encryption (defuse/php-encryption)
 *
 * Different paths are used for local Windows development vs production.
 *
 * @package Publish
 * @author  MDWiki Team
 * @since   1.0.0
 */

// Environment detection based on directory path
if (substr(__DIR__, 0, 2) === 'I:') {
    // Local Windows development - load from local auth repository
    include_once 'I:/mdwiki/auth_repo/vendor_load.php';
} else {
    // Production - load from relative auth directory
    include_once __DIR__ . '/../auth/vendor_load.php';
}
