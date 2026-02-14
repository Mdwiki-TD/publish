<?php

declare(strict_types=1);

/**
 * Alternative entry point for the MDWiki publishing API.
 *
 * This file simply includes the main index.php entry point.
 * It exists for backward compatibility with systems that
 * reference main.php instead of index.php.
 *
 * @package Publish
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see index.php The main entry point
 */

include_once __DIR__ . '/index.php';
