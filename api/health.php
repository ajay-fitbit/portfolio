<?php
/**
 * Health Check API
 * Check if Vector Store (OpenAI or ChromaDB) and OpenAI API are accessible
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../includes/env_loader.php');

// Load environment variables
EnvLoader::load(__DIR__ . '/../.env');

// Check which vector store is being used
$useOpenAIVectorStore = !empty(EnvLoader::get('OPENAI_VECTOR_STORE_ID'));
$openaiApiKey = EnvLoader::get('OPENAI_API_KEY');
$promptId = EnvLoader::get('OPENAI_PROMPT_ID');

$status = [
    'vector_store' => 'offline',
    'vector_store_type' => $useOpenAIVectorStore ? 'openai' : 'chromadb',
    'openai_api' => 'unknown',
    'prompt_id' => !empty($promptId) ? 'configured' : 'not_configured',
    'can_chat' => false,
    'overall_status' => 'offline'
];

// Check Vector Store
if ($useOpenAIVectorStore) {
    // Check OpenAI Vector Store
    try {
        require_once(__DIR__ . '/openai_vector_client.php');
        $client = new OpenAIVectorClient();
        
        if ($client->isServerRunning()) {
            $status['vector_store'] = 'online';
        }
    } catch (Exception $e) {
        // Vector Store is offline or misconfigured
        $status['error'] = $e->getMessage();
    }
} else {
    // Check ChromaDB server
    $chromaUrl = EnvLoader::get('CHROMA_URL', 'http://localhost:8000');
    try {
        $ch = curl_init($chromaUrl . '/api/v1/heartbeat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Quick timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $status['vector_store'] = 'online';
        }
    } catch (Exception $e) {
        // ChromaDB is offline
    }
}

// Check OpenAI API key
if (!empty($openaiApiKey)) {
    $status['openai_api'] = 'configured';
} else {
    $status['openai_api'] = 'not_configured';
}

// Can only chat when the OpenAI API key, vector store, and prompt are configured.
$status['can_chat'] = (
    $status['openai_api'] === 'configured' &&
    $status['prompt_id'] === 'configured' &&
    ($useOpenAIVectorStore ? !empty(EnvLoader::get('OPENAI_VECTOR_STORE_ID')) : true)
);

// Overall status
if ($status['can_chat'] && $status['vector_store'] === 'online') {
    $status['overall_status'] = 'online';
} elseif ($status['can_chat']) {
    $status['overall_status'] = 'degraded';
} else {
    $status['overall_status'] = 'offline';
}

echo json_encode([
    'status' => 'success',
    'data' => $status,
    'timestamp' => time()
]);
