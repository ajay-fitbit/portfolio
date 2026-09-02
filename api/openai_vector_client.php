<?php
/**
 * OpenAI Vector Store Client
 * Handles all interactions with OpenAI Vector Stores (replaces ChromaDB)
 */

require_once(__DIR__ . '/../includes/env_loader.php');

class OpenAIVectorClient {
    private $openaiApiKey;
    private $vectorStoreId;
    private $promptId;  // Changed from assistantId
    private $llmModel;
    private $embeddingModel;
    
    public function __construct() {
        // Load environment variables
        EnvLoader::load(__DIR__ . '/../.env');
        
        $this->openaiApiKey = EnvLoader::get('OPENAI_API_KEY');
        $this->vectorStoreId = EnvLoader::get('OPENAI_VECTOR_STORE_ID'); // Your storage ID
        $this->promptId = EnvLoader::get('OPENAI_PROMPT_ID'); // Changed: Prompt ID instead of Assistant ID
        $this->llmModel = EnvLoader::get('LLM_MODEL', 'gpt-4o-mini');
        $this->embeddingModel = EnvLoader::get('EMBEDDING_MODEL', 'text-embedding-3-small');
        
        if (empty($this->openaiApiKey)) {
            throw new Exception('OPENAI_API_KEY not set in .env file');
        }
        
        if (empty($this->vectorStoreId)) {
            throw new Exception('OPENAI_VECTOR_STORE_ID not set in .env file');
        }
        
        if (empty($this->promptId)) {
            throw new Exception('OPENAI_PROMPT_ID not set in .env file. Please create a prompt in the OpenAI dashboard.');
        }
    }
    
    /**
     * Check if Vector Store is accessible
     */
    public function isServerRunning() {
        try {
            $vectorStore = $this->getVectorStoreInfo();
            return isset($vectorStore['id']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get Vector Store information
     */
    public function getVectorStoreInfo() {
        $url = "https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}";
        return $this->makeOpenAIRequest($url, null, 'GET');
    }
    
    /**
     * Search Vector Store and generate response
     * Uses new Responses API (replaces Assistants API)
     */
    public function searchAndRespond($userMessage, $temperature = 0.7, $maxTokens = 1000, $streamCallback = null) {
        // Build system instructions
        $systemInstructions = <<<EOT
You are Ajay Singh's Portfolio Assistant, responding as if you ARE Ajay Singh himself.

CRITICAL INSTRUCTIONS:
- Answer ONLY based on the provided knowledge base from the files
- Respond in FIRST PERSON (use "I", "my", "me") as if you are Ajay Singh speaking directly
- Be professional, friendly, interactive and conversational
- If the answer is not in the knowledge base, say: "I don't have that specific information in my portfolio."
- Share your experiences, skills, and projects in first person (e.g., "I have experience in...", "My skills include...", "I worked on...")
- Provide specific details when available (years of experience, technologies, project names)
- Do not make up or hallucinate any information
- Use the file search results to provide accurate answers

Now answer the user's question as Ajay Singh, speaking in first person, based on the available files.
EOT;

        // Use the new Responses API with conversation management
        return $this->queryWithResponse($userMessage, $systemInstructions, $temperature, $maxTokens, $streamCallback);
    }
    
    /**
     * Query using new Responses API (replaces queryWithAssistant)
     */
    public function queryWithResponse($userMessage, $systemInstructions = null, $temperature = 0.7, $maxTokens = 1000, $streamCallback = null) {
        if (empty($this->promptId)) {
            throw new Exception('OPENAI_PROMPT_ID not configured. Please create a prompt in the OpenAI dashboard.');
        }
        
        // Create a conversation (replaces thread)
        $conversationUrl = 'https://api.openai.com/v1/conversations';
        $conversation = $this->makeOpenAIRequest($conversationUrl, [], 'POST');
        $conversationId = $conversation['id'];
        
        // Create response (replaces run - much simpler, no polling!)
        $responseUrl = 'https://api.openai.com/v1/responses';
        $responseData = [
            'prompt' => [
                'id' => $this->promptId,
                'version' => '1'  // Required: prompt version
            ],
            'conversation' => $conversationId,
            'input' => [
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ]
        ];
        
        // Add optional parameters
        if ($temperature !== null) {
            $responseData['temperature'] = $temperature;
        }
        if ($maxTokens !== null) {
            $responseData['max_output_tokens'] = $maxTokens;
        }

        if ($streamCallback) {
            $responseData['stream'] = true;
        }

        if ($streamCallback) {
            $replyText = '';
            $sources = [];
            $finalResponse = null;

            $this->makeOpenAIStreamingRequest($responseUrl, $responseData, function ($event, $payload) use (&$replyText, &$sources, &$finalResponse, $streamCallback) {
                if (is_array($payload)) {
                    if (($event === 'response.output_text.delta' || $event === 'output_text.delta') && isset($payload['delta'])) {
                        $replyText .= $payload['delta'];
                        $streamCallback($payload['delta']);
                    }

                    if ($event === 'response.completed' || $event === 'response.done') {
                        $finalResponse = $payload;
                        $extracted = $this->extractReplyAndSourcesFromResponse($payload);
                        if (empty($replyText) && !empty($extracted['reply'])) {
                            $replyText = $extracted['reply'];
                        }
                        $sources = array_values(array_unique(array_merge($sources, $extracted['sources'])));
                    }
                }
            });

            if ($finalResponse === null) {
                error_log('WARNING: Streamed OpenAI response finished without a completed event.');
            }

            return [
                'reply' => trim($replyText),
                'sources' => array_unique($sources),
                'conversation_id' => $conversationId
            ];
        }

        // Make the request - no polling needed!
        $response = $this->makeOpenAIRequest($responseUrl, $responseData, 'POST');

        return $this->extractReplyAndSourcesFromResponse($response) + [
            'conversation_id' => $conversationId
        ];
    }

    private function extractReplyAndSourcesFromResponse($response) {
        // Log the raw response for debugging
        error_log("OpenAI Response API raw response: " . json_encode($response));

        $replyText = '';
        $sources = [];

        // The response structure is: response.output[].content[].text
        if (isset($response['output']) && is_array($response['output'])) {
            foreach ($response['output'] as $item) {
                if (isset($item['role']) && $item['role'] === 'assistant' &&
                    isset($item['type']) && $item['type'] === 'message') {

                    if (isset($item['content']) && is_array($item['content'])) {
                        foreach ($item['content'] as $content) {
                            if (isset($content['type']) && $content['type'] === 'output_text') {
                                $replyText .= $content['text'] ?? '';
                            }

                            if (isset($content['annotations']) && is_array($content['annotations'])) {
                                foreach ($content['annotations'] as $annotation) {
                                    if (isset($annotation['file_citation']['file_id'])) {
                                        $sources[] = $annotation['file_citation']['file_id'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($replyText) && isset($response['output_text'])) {
            $replyText = $response['output_text'];
        }

        if (empty($replyText)) {
            error_log("WARNING: Could not extract reply text from response. Response keys: " . implode(', ', array_keys($response)));
            error_log("Full response structure: " . json_encode($response, JSON_PRETTY_PRINT));
        }

        return [
            'reply' => trim($replyText),
            'sources' => array_unique($sources)
        ];
    }
    
    /**
     * Upload file to Vector Store
     */
    public function uploadFile($filePath, $purpose = 'assistants') {
        // First, upload the file
        $uploadUrl = 'https://api.openai.com/v1/files';
        
        $ch = curl_init($uploadUrl);
        
        $headers = [
            'Authorization: Bearer ' . $this->openaiApiKey
        ];
        
        $postData = [
            'file' => new CURLFile($filePath),
            'purpose' => $purpose
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // SSL options for development
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error during file upload: ' . $error);
        }
        
        curl_close($ch);
        
        $fileData = json_decode($response, true);
        
        if ($httpCode !== 200) {
            throw new Exception('File upload failed: ' . ($fileData['error']['message'] ?? $response));
        }
        
        $fileId = $fileData['id'];
        
        // Add file to vector store
        $addUrl = "https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files";
        $addData = ['file_id' => $fileId];
        
        return $this->makeOpenAIRequest($addUrl, $addData, 'POST');
    }
    
    /**
     * List files in Vector Store
     */
    public function listFiles() {
        $url = "https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files";
        return $this->makeOpenAIRequest($url, null, 'GET');
    }
    
    /**
     * Get document count
     */
    public function getDocumentCount() {
        $vectorStore = $this->getVectorStoreInfo();
        return $vectorStore['file_counts']['total'] ?? 0;
    }
    
    /**
     * Delete file from Vector Store
     */
    public function deleteFile($fileId) {
        $url = "https://api.openai.com/v1/vector_stores/{$this->vectorStoreId}/files/{$fileId}";
        return $this->makeOpenAIRequest($url, null, 'DELETE');
    }
    
    /**
     * Extract sources from chat completion response
     */
    private function extractSources($response) {
        $sources = [];
        
        if (isset($response['choices'][0]['message']['tool_calls'])) {
            foreach ($response['choices'][0]['message']['tool_calls'] as $toolCall) {
                if ($toolCall['type'] === 'file_search') {
                    // Extract file references if available
                    $sources[] = 'file_search_used';
                }
            }
        }
        
        return $sources;
    }
    
    /**
     * Extract sources from assistant annotations
     */
    private function extractSourcesFromAnnotations($annotations) {
        $sources = [];
        
        foreach ($annotations as $annotation) {
            if ($annotation['type'] === 'file_citation') {
                $sources[] = $annotation['file_citation']['file_id'] ?? 'unknown';
            }
        }
        
        return array_unique($sources);
    }
    
    /**
     * Make HTTP request to OpenAI API
     */
    private function makeOpenAIRequest($url, $data, $method = 'POST') {
        $ch = curl_init($url);
        
        // Build headers array - Updated for Responses API
        $headers = [
            'Authorization: Bearer ' . $this->openaiApiKey,
            'Content-Type: application/json'
        ];
        
        // Only add OpenAI-Beta header for vector store endpoints
        if (strpos($url, '/vector_stores') !== false) {
            $headers[] = 'OpenAI-Beta: assistants=v2';
        }
        
        // Set common curl options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
        
        // Capture verbose output
        $verboseLog = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verboseLog);
        
        // SSL options - For development environment
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            // FIXED: Handle both null and empty array, check JSON encoding
            if ($data !== null) {
                $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($jsonData === false) {
                    curl_close($ch);
                    throw new Exception('Failed to encode JSON data: ' . json_last_error_msg());
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            } else {
                // Send empty JSON object if data is null
                curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        // Get verbose log
        rewind($verboseLog);
        $verboseOutput = stream_get_contents($verboseLog);
        fclose($verboseLog);
        
        curl_close($ch);
        
        // FIXED: Check for curl errors with error number
        if ($curlErrno) {
            error_log("Curl verbose output: " . $verboseOutput);
            throw new Exception("Curl error ($curlErrno): $curlError | URL: $url");
        }
        
        // FIXED: Check if response is empty
        if ($response === false || $response === '') {
            error_log("Empty response debug | URL: $url | Method: $method | HTTP Code: $httpCode | Verbose: " . $verboseOutput);
            error_log("Request headers: " . json_encode($headers));
            error_log("Request data: " . json_encode($data));
            error_log("API Key (first 20 chars): " . substr($this->openaiApiKey, 0, 20) . "...");
            
            throw new Exception("Empty response from OpenAI API | HTTP Code: $httpCode | URL: $url | This usually means invalid API key or network issue. Check error logs for details.");
        }
        
        // FIXED: Decode JSON and check for errors
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = json_last_error_msg();
            error_log("JSON decode error: $jsonError | Response: " . substr($response, 0, 500));
            throw new Exception("Failed to decode JSON response: $jsonError | HTTP Code: $httpCode | Response preview: " . substr($response, 0, 200));
        }
        
        // FIXED: Better error handling with more details
        if ($httpCode >= 400) {
            $errorMsg = "OpenAI API error (HTTP $httpCode)";
            
            // Try different error response structures
            if (isset($decoded['error']['message'])) {
                $errorMsg .= ': ' . $decoded['error']['message'];
                if (isset($decoded['error']['type'])) {
                    $errorMsg .= ' [Type: ' . $decoded['error']['type'] . ']';
                }
            } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
                $errorMsg .= ': ' . $decoded['error'];
            } elseif (isset($decoded['message'])) {
                $errorMsg .= ': ' . $decoded['message'];
            } else {
                $errorMsg .= ' | Response: ' . substr($response, 0, 300);
            }
            
            // Log full error for debugging
            error_log("OpenAI API Error | URL: $url | Method: $method | HTTP Code: $httpCode | Full Response: $response");
            
            throw new Exception($errorMsg);
        }
        
        return $decoded;
    }

    private function makeOpenAIStreamingRequest($url, $data, $eventCallback) {
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->openaiApiKey,
            'Content-Type: application/json',
            'Accept: text/event-stream'
        ];

        if (strpos($url, '/vector_stores') !== false) {
            $headers[] = 'OpenAI-Beta: assistants=v2';
        }

        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new Exception('Failed to encode streaming request payload: ' . json_last_error_msg());
        }

        $buffer = '';

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, $eventCallback) {
            $buffer .= $chunk;

            while (($separatorPosition = strpos($buffer, "\n\n")) !== false) {
                $rawEvent = substr($buffer, 0, $separatorPosition);
                $buffer = substr($buffer, $separatorPosition + 2);
                $this->processStreamEventBlock($rawEvent, $eventCallback);
            }

            return strlen($chunk);
        });

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);

        if (!empty($buffer)) {
            $this->processStreamEventBlock($buffer, $eventCallback);
        }

        curl_close($ch);

        if ($curlErrno) {
            throw new Exception("Curl streaming error ($curlErrno): $curlError | URL: $url");
        }

        if ($response === false && $httpCode >= 400) {
            throw new Exception("Streaming request failed with HTTP $httpCode | URL: $url");
        }

        return true;
    }

    private function processStreamEventBlock($block, $eventCallback) {
        $lines = preg_split('/\r?\n/', $block);
        $event = 'message';
        $dataLines = [];

        foreach ($lines as $line) {
            if (strpos($line, 'event:') === 0) {
                $event = trim(substr($line, 6));
            } elseif (strpos($line, 'data:') === 0) {
                $dataLines[] = ltrim(substr($line, 5));
            }
        }

        $dataText = trim(implode("\n", $dataLines));
        if ($dataText === '') {
            return;
        }

        if ($dataText === '[DONE]') {
            $eventCallback('done', null);
            return;
        }

        $payload = json_decode($dataText, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $payload = ['raw' => $dataText];
        }

        $eventCallback($event, $payload);
    }
}
?>
