<?php
/**
 * Chroma Vector Database Client
 * Handles all interactions with Chroma DB and OpenAI API
 */

require_once(__DIR__ . '/../includes/env_loader.php');

class ChromaClient {
    private $chromaUrl;
    private $collectionName;
    private $collectionId;
    private $openaiApiKey;
    private $embeddingModel;
    private $llmModel;
    
    public function __construct() {
        // Load environment variables
        EnvLoader::load(__DIR__ . '/../.env');
        
        $this->chromaUrl = EnvLoader::get('CHROMA_URL', 'http://localhost:8000');
        $this->collectionName = EnvLoader::get('CHROMA_COLLECTION', 'portfolio_kb');
        $this->openaiApiKey = EnvLoader::get('OPENAI_API_KEY');
        $this->embeddingModel = EnvLoader::get('EMBEDDING_MODEL', 'text-embedding-3-small');
        $this->llmModel = EnvLoader::get('LLM_MODEL', 'gpt-4o-mini');
        
        if (empty($this->openaiApiKey)) {
            throw new Exception('OPENAI_API_KEY not set in .env file');
        }
        
        // Get collection ID for API calls
        $this->collectionId = null;
    }
    
    /**
     * Initialize collection ID by fetching it from Chroma
     */
    private function ensureCollectionId() {
        if ($this->collectionId !== null) {
            return;
        }
        
        $collectionInfo = $this->getCollectionInfo();
        if (isset($collectionInfo['id'])) {
            $this->collectionId = $collectionInfo['id'];
        } else {
            throw new Exception('Could not retrieve collection ID for: ' . $this->collectionName);
        }
    }
    
    /**
     * Generate embedding for text using OpenAI API
     */
    public function generateEmbedding($text) {
        $url = 'https://api.openai.com/v1/embeddings';
        
        $data = [
            'input' => $text,
            'model' => $this->embeddingModel
        ];
        
        $response = $this->makeOpenAIRequest($url, $data);
        
        if (isset($response['data'][0]['embedding'])) {
            return $response['data'][0]['embedding'];
        }
        
        throw new Exception('Failed to generate embedding: ' . json_encode($response));
    }
    
    /**
     * Store vector in Chroma database
     */
    public function storeVector($id, $text, $embedding, $metadata = []) {
        // Ensure we have collection ID
        $this->ensureCollectionId();
        
        $url = $this->chromaUrl . '/api/v1/collections/' . $this->collectionId . '/add';
        
        // Ensure ID is a string
        $id = (string)$id;
        
        $data = [
            'ids' => [$id],
            'documents' => [$text],
            'embeddings' => [$embedding],
            'metadatas' => [$metadata]
        ];
        
        return $this->makeChromaRequest($url, $data, 'POST');
    }
    
    /**
     * Query Chroma for similar vectors
     */
    public function queryVectors($queryEmbedding, $topK = 5) {
        // Ensure we have collection ID
        $this->ensureCollectionId();
        
        $url = $this->chromaUrl . '/api/v1/collections/' . $this->collectionId . '/query';
        
        $data = [
            'query_embeddings' => [$queryEmbedding],
            'n_results' => $topK
        ];
        
        $response = $this->makeChromaRequest($url, $data, 'POST');
        
        // Format the response
        $results = [];
        if (isset($response['documents'][0])) {
            foreach ($response['documents'][0] as $index => $document) {
                $results[] = [
                    'id' => $response['ids'][0][$index] ?? null,
                    'document' => $document,
                    'distance' => $response['distances'][0][$index] ?? null,
                    'metadata' => $response['metadatas'][0][$index] ?? []
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Generate chat completion using OpenAI
     */
    public function generateChatCompletion($messages, $temperature = 0.7, $maxTokens = 1000) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->llmModel,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];
        
        $response = $this->makeOpenAIRequest($url, $data);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        throw new Exception('Failed to generate response: ' . json_encode($response));
    }
    
    /**
     * Get collection info
     */
    public function getCollectionInfo() {
        $url = $this->chromaUrl . '/api/v1/collections/' . $this->collectionName;
        return $this->makeChromaRequest($url, null, 'GET');
    }
    
    /**
     * Get document count in collection
     */
    public function getDocumentCount() {
        // Ensure we have collection ID
        $this->ensureCollectionId();
        
        $url = $this->chromaUrl . '/api/v1/collections/' . $this->collectionId . '/count';
        $count = $this->makeChromaRequest($url, null, 'GET');
        
        // The count endpoint returns just a number
        return is_numeric($count) ? (int)$count : 0;
    }
    
    /**
     * Delete collection (for resetting)
     */
    public function deleteCollection() {
        $url = $this->chromaUrl . '/api/v1/collections/' . $this->collectionName;
        return $this->makeChromaRequest($url, null, 'DELETE');
    }
    
    /**
     * Create collection
     */
    public function createCollection() {
        $url = $this->chromaUrl . '/api/v1/collections';
        $data = [
            'name' => $this->collectionName,
            'metadata' => ['description' => 'Portfolio Knowledge Base']
        ];
        return $this->makeChromaRequest($url, $data, 'POST');
    }
    
    /**
     * Check if Chroma server is running
     */
    public function isServerRunning() {
        try {
            $url = $this->chromaUrl . '/api/v1/heartbeat';
            $response = $this->makeChromaRequest($url, null, 'GET');
            return isset($response['nanosecond heartbeat']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Make HTTP request to OpenAI API
     */
    private function makeOpenAIRequest($url, $data) {
        $ch = curl_init($url);
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiApiKey
        ];
        
        // Ensure proper JSON encoding with Unicode handling
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($jsonData === false) {
            throw new Exception('JSON encoding error: ' . json_last_error_msg());
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // SSL options - for development only
        // TODO: In production, remove these and use proper SSL certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error: ' . $error);
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($httpCode !== 200) {
            throw new Exception('OpenAI API error: ' . ($decoded['error']['message'] ?? $response));
        }
        
        return $decoded;
    }
    
    /**
     * Make HTTP request to Chroma API
     */
    private function makeChromaRequest($url, $data = null, $method = 'GET') {
        $ch = curl_init($url);
        
        $headers = ['Content-Type: application/json'];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error connecting to Chroma: ' . $error);
        }
        
        curl_close($ch);
        
        // Chroma might return empty response for some operations
        if (empty($response)) {
            return ['success' => true, 'http_code' => $httpCode];
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Chroma API error: ' . ($decoded['error'] ?? $response));
        }
        
        return $decoded;
    }
    
    /**
     * Split text into chunks for embedding
     */
    public function chunkText($text, $chunkSize = 400, $overlap = 50) {
        // Split by sentences first
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $chunks = [];
        $currentChunk = '';
        $currentWordCount = 0;
        
        foreach ($sentences as $sentence) {
            $wordCount = str_word_count($sentence);
            
            if ($currentWordCount + $wordCount > $chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                
                // Add overlap
                $words = explode(' ', $currentChunk);
                $overlapWords = array_slice($words, -$overlap);
                $currentChunk = implode(' ', $overlapWords) . ' ' . $sentence;
                $currentWordCount = count($overlapWords) + $wordCount;
            } else {
                $currentChunk .= ' ' . $sentence;
                $currentWordCount += $wordCount;
            }
        }
        
        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }
}
?>
