<?php
/**
 * Chat Endpoint - Handles user queries using RAG
 */

header('Content-Type: application/json');

// Choose which vector store to use
// Option 1: Use OpenAI Vector Store (recommended if you have storage)
require_once(__DIR__ . '/openai_vector_client.php');

// Option 2: Use ChromaDB (comment out above, uncomment below)
// require_once(__DIR__ . '/chroma_client.php');

require_once(__DIR__ . '/../includes/config.php');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Make $conn globally accessible
global $conn;

function sendStreamEvent($event, $data = []) {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

    if (function_exists('ob_flush')) {
        @ob_flush();
    }

    flush();
}

try {
    // Option 1: OpenAI Vector Store
    $client = new OpenAIVectorClient();
    
    // Option 2: ChromaDB (comment out above, uncomment below)
    // $client = new ChromaClient();
    
    // Check if Vector Store/Chroma server is accessible
    if (!$client->isServerRunning()) {
        throw new Exception('Vector store is not accessible. Please check your configuration.');
    }
    
    // Get user message from JSON payload, form-encoded payloads, or query strings.
    // Some PHP/Apache setups do not populate php://input consistently with raw JSON.
    $rawInput = file_get_contents('php://input');
    $input = [];

    if (!empty($rawInput)) {
        $decodedInput = json_decode($rawInput, true);
        if (is_array($decodedInput) && !empty($decodedInput)) {
            $input = $decodedInput;
        } else {
            parse_str($rawInput, $input);
        }
    }

    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }

    if (empty($input) && !empty($_GET)) {
        $input = $_GET;
    }

    $userMessage = trim((string)($input['message'] ?? ''));
    $sessionId = (string)($input['session_id'] ?? session_id());
    $useStreaming = !empty($input['stream']);
    
    if (empty($userMessage)) {
        throw new Exception('Message is required');
    }

    if ($useStreaming) {
        header_remove('Content-Type');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Disable PHP/Apache buffering so each SSE delta reaches the browser immediately.
        ini_set('output_buffering', '0');
        ini_set('implicit_flush', '1');
        ini_set('zlib.output_compression', '0');
        ini_set('zlib.output_handler', '');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_implicit_flush(true);
        ignore_user_abort(true);
        set_time_limit(0);
    }
    
    // Log the query
    logChat($conn, $sessionId, 'user', $userMessage);
    
    // Generate response using RAG pipeline
    $startTime = microtime(true);
    $streamBuffer = '';
    $response = generateRAGResponse($client, $userMessage, $useStreaming ? function ($delta) use (&$streamBuffer) {
        $streamBuffer .= $delta;
        sendStreamEvent('delta', ['text' => $delta]);
    } : null);
    $responseTime = round((microtime(true) - $startTime) * 1000); // in milliseconds
    
    // Log the response
    logChat($conn, $sessionId, 'assistant', $response['reply'], $response['sources']);
    
    // Add response time
    $response['response_time_ms'] = $responseTime;
    $response['session_id'] = $sessionId;

    if ($useStreaming) {
        sendStreamEvent('done', $response);
    } else {
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Chat API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    
    // In development, show detailed errors; in production, hide them
    $errorResponse = [
        'status' => 'error',
        'reply' => 'I apologize, but I encountered an error processing your request. Please try again.'
    ];
    
    // Show detailed error in development mode
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $errorResponse['error_details'] = $e->getMessage();
        $errorResponse['error_file'] = $e->getFile();
        $errorResponse['error_line'] = $e->getLine();
    }

    if (isset($useStreaming) && $useStreaming) {
        sendStreamEvent('error', $errorResponse);
    } else {
        echo json_encode($errorResponse);
    }
}

/**
 * Generate response using RAG pipeline
 */
function generateRAGResponse($client, $userMessage, $streamCallback = null) {
    $maxTokens = (int) EnvLoader::get('MAX_TOKENS', 1000);
    $temperature = (float) EnvLoader::get('TEMPERATURE', 0.7);
    
    // Check if using OpenAI Vector Store or ChromaDB
    if ($client instanceof OpenAIVectorClient) {
        // OpenAI Vector Store - uses built-in file search
        $result = $client->searchAndRespond($userMessage, $temperature, $maxTokens, $streamCallback);
        
        return [
            'status' => 'success',
            'reply' => $result['reply'],
            'sources' => $result['sources'],
            'context_chunks_used' => count($result['sources']),
            'conversation_id' => $result['conversation_id'] ?? null // New Responses API uses conversations
        ];
        
    } else {
        // ChromaDB - traditional RAG pipeline
        $topK = (int) EnvLoader::get('TOP_K_RESULTS', 5);
        
        // Step 1: Generate embedding for user query
        $queryEmbedding = $client->generateEmbedding($userMessage);
        
        // Step 2: Retrieve relevant context from Chroma
        $retrievedDocs = $client->queryVectors($queryEmbedding, $topK);
        
        // Check if we found any relevant documents
        if (empty($retrievedDocs)) {
            return [
                'status' => 'success',
                'reply' => "I don't have that information in my portfolio. Could you please ask something else about Ajay Singh's experience, skills, or projects?",
                'sources' => []
            ];
        }
        
        // Step 3: Build context from retrieved documents
        $context = buildContext($retrievedDocs);
        
        // Step 4: Construct prompt
        $systemPrompt = <<<EOT
You are Ajay Singh's Portfolio Assistant, responding as if you ARE Ajay Singh himself.

CRITICAL INSTRUCTIONS:
- Answer ONLY based on the provided knowledge base below
- Respond in FIRST PERSON (use "I", "my", "me") as if you are Ajay Singh speaking directly
- Keep answers BRIEF and CONCISE (2-3 sentences maximum unless user asks for details)
- Be professional, friendly, and conversational
- If the answer is not in the knowledge base, say: "I don't have that specific information."
- Share experiences, skills, and projects in first person (e.g., "I have experience in...", "My skills include...")
- Only provide detailed explanations when user explicitly asks for more details (e.g., "tell me more", "explain in detail")
- Do not make up or hallucinate any information
- NEVER refer to yourself in third person (NO "he", "his", "Ajay Singh")

KNOWLEDGE BASE:
$context

Now answer the user's question as Ajay Singh, speaking in first person, giving brief answers unless details are requested.
EOT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ];
        
        // Step 5: Generate response using LLM
        $reply = $client->generateChatCompletion($messages, $temperature, $maxTokens);
        
        // Extract source IDs
        $sources = array_map(function($doc) {
            return $doc['id'];
        }, $retrievedDocs);
        
        return [
            'status' => 'success',
            'reply' => $reply,
            'sources' => $sources,
            'context_chunks_used' => count($retrievedDocs)
        ];
    }
}

/**
 * Build context string from retrieved documents
 */
function buildContext($retrievedDocs) {
    $contextParts = [];
    
    foreach ($retrievedDocs as $index => $doc) {
        $source = $doc['metadata']['source'] ?? 'Unknown';
        $chunkIndex = $doc['metadata']['chunk_index'] ?? $index;
        
        $contextParts[] = sprintf(
            "[Source: %s, Chunk: %d]\n%s",
            $source,
            $chunkIndex,
            $doc['document']
        );
    }
    
    return implode("\n\n---\n\n", $contextParts);
}

/**
 * Log chat message to database
 */
function logChat($conn, $sessionId, $role, $message, $sources = null) {
    // Create session if it doesn't exist
    if ($role === 'user') {
        createSessionIfNotExists($conn, $sessionId);
    }
    
    // Create table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS chat_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) NOT NULL,
        role ENUM('user', 'assistant', 'system') NOT NULL,
        message TEXT NOT NULL,
        sources JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_created (created_at)
    )";
    
    $conn->query($createTableSQL);
    
    // Insert chat log
    $stmt = $conn->prepare("INSERT INTO chat_logs (session_id, role, message, sources) VALUES (?, ?, ?, ?)");
    $sourcesJson = $sources ? json_encode($sources) : null;
    $stmt->bind_param("ssss", $sessionId, $role, $message, $sourcesJson);
    $stmt->execute();
    $stmt->close();
    
    // Update session activity
    if ($role === 'user') {
        updateSessionActivity($conn, $sessionId);
    }
}

/**
 * Create session record if it doesn't exist
 */
function createSessionIfNotExists($conn, $sessionId) {
    $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $checkStmt = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ?");
    $checkStmt->bind_param("s", $sessionId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $insertStmt = $conn->prepare("INSERT INTO chat_sessions (session_id, user_ip, user_agent) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $sessionId, $userIp, $userAgent);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    $checkStmt->close();
}

/**
 * Update session last activity and message count
 */
function updateSessionActivity($conn, $sessionId) {
    $updateStmt = $conn->prepare("UPDATE chat_sessions SET last_activity = NOW(), message_count = message_count + 1 WHERE session_id = ?");
    $updateStmt->bind_param("s", $sessionId);
    $updateStmt->execute();
    $updateStmt->close();
}
?>
