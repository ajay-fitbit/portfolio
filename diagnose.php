<?php
/**
 * Production Diagnostics - Check OpenAI Vector Store Connection
 */
header('Content-Type: text/plain');

echo "=== Production Diagnostics ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check if files exist
echo "=== File Check ===\n";
$files = [
    'includes/env_loader.php',
    'api/openai_vector_client.php',
    'api/ingest.php',
    '.env'
];

foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "$file: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

echo "\n=== Environment Variables ===\n";
try {
    require_once(__DIR__ . '/includes/env_loader.php');
    EnvLoader::load(__DIR__ . '/.env');
    
    echo "OPENAI_API_KEY: " . (EnvLoader::get('OPENAI_API_KEY') ? "✓ Set (" . strlen(EnvLoader::get('OPENAI_API_KEY')) . " chars)" : "✗ NOT SET") . "\n";
    echo "OPENAI_VECTOR_STORE_ID: " . (EnvLoader::get('OPENAI_VECTOR_STORE_ID') ? "✓ " . EnvLoader::get('OPENAI_VECTOR_STORE_ID') : "✗ NOT SET") . "\n";
    echo "OPENAI_ASSISTANT_ID: " . (EnvLoader::get('OPENAI_ASSISTANT_ID') ? "✓ " . EnvLoader::get('OPENAI_ASSISTANT_ID') : "✗ NOT SET") . "\n";
    echo "API_BASE_PATH: " . (EnvLoader::get('API_BASE_PATH') ? EnvLoader::get('API_BASE_PATH') : "NOT SET") . "\n";
    
} catch (Exception $e) {
    echo "✗ Error loading environment: " . $e->getMessage() . "\n";
}

echo "\n=== OpenAI Client Test ===\n";
try {
    require_once(__DIR__ . '/api/openai_vector_client.php');
    $client = new OpenAIVectorClient();
    echo "✓ Client initialized\n";
    
    $isRunning = $client->isServerRunning();
    echo "Server running: " . ($isRunning ? "✓ YES" : "✗ NO") . "\n";
    
    if ($isRunning) {
        $info = $client->getVectorStoreInfo();
        echo "Vector Store ID: " . ($info['id'] ?? 'N/A') . "\n";
        echo "Status: " . ($info['status'] ?? 'N/A') . "\n";
        echo "Files: " . ($info['file_counts']['total'] ?? 0) . "\n";
        echo "Usage: " . ($info['usage_bytes'] ?? 0) . " bytes\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "cURL enabled: " . (function_exists('curl_init') ? "✓ YES" : "✗ NO") . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? "✓ Enabled" : "✗ Disabled") . "\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? "✓ Enabled" : "✗ Disabled") . "\n";

echo "\n=== Ingest.php Syntax Check ===\n";
$ingestFile = __DIR__ . '/api/ingest.php';
if (file_exists($ingestFile)) {
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($ingestFile) . " 2>&1", $output, $returnCode);
    if ($returnCode === 0) {
        echo "✓ No syntax errors\n";
    } else {
        echo "✗ Syntax error:\n";
        echo implode("\n", $output) . "\n";
    }
} else {
    echo "✗ File not found\n";
}

echo "\n=== Test Complete ===\n";
