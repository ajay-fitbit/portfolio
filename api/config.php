<?php
/**
 * Client Configuration API
 * Exposes necessary configuration to JavaScript
 */

header('Content-Type: application/javascript');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../includes/env_loader.php');

// Load environment variables
EnvLoader::load(__DIR__ . '/../.env');

// Detect the app base path from the current request so the same codebase
// works whether it is mounted under /itshitechs/portfolio/ or
// /itshitechs.com/portfolio/.
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api/config.php';
$apiBasePath = rtrim(dirname(dirname($scriptName)), '/');

if ($apiBasePath === '') {
    $apiBasePath = '/';
}

// Output as JavaScript constant
echo "window.PORTFOLIO_CONFIG = " . json_encode([
    'apiBasePath' => $apiBasePath
]) . ";";
