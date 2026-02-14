<?php

declare(strict_types=1);

/**
 * Main entry point for the MDWiki article publishing API.
 *
 * This file serves as the HTTP endpoint for article publishing requests.
 * It handles CORS validation, includes dependencies, and delegates to
 * the start.php workflow.
 *
 * Expected POST parameters:
 * - user: Wikipedia username
 * - title: Target article title
 * - target: Target language code (e.g., 'ar', 'fr')
 * - sourcetitle: Source MDWiki article title
 * - text: Article wikitext content
 * - campaign: Optional campaign identifier
 *
 * @package Publish
 * @author  MDWiki Team
 * @since   1.0.0
 *
 * @see start.php for the main processing logic
 */

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/bots/cors.php';

use function Publish\CORS\is_allowed;

// CORS validation is currently disabled for development
// SECURITY WARNING: Re-enable in production
// @see ANALYSIS_REPORT.md SEC-004
// $alowed = is_allowed();
// if (!$alowed) {
//     http_response_code(403); // Forbidden
//     echo json_encode(['error' => 'Access denied. Requests are only allowed from authorized domains.']);
//     exit;
// }
// header("Access-Control-Allow-Origin: https://$alowed");

// Include all dependencies
include_once __DIR__ . '/include.php';

// Include and execute the main workflow
include_once __DIR__ . '/start.php';
