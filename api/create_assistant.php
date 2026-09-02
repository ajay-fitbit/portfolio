<?php
/**
 * Create OpenAI Assistant with Vector Store
 * Run this once to create the assistant and get the ID
 */

require_once(__DIR__ . '/../includes/env_loader.php');
EnvLoader::load(__DIR__ . '/../.env');

$apiKey = EnvLoader::get('OPENAI_API_KEY');
$vectorStoreId = EnvLoader::get('OPENAI_VECTOR_STORE_ID');
$model = EnvLoader::get('LLM_MODEL', 'gpt-4o-mini');

if (empty($apiKey) || empty($vectorStoreId)) {
    die("ERROR: OPENAI_API_KEY and OPENAI_VECTOR_STORE_ID must be set in .env\n");
}

echo "Creating OpenAI Assistant...\n\n";

$url = 'https://api.openai.com/v1/assistants';

$data = [
    'name' => 'Ajay Singh Portfolio Assistant',
    'description' => 'AI assistant that answers questions about Ajay Singh\'s professional background, skills, and experience',
    'model' => $model,
    'instructions' => <<<EOT
You are Ajay Singh's Portfolio Assistant, a helpful AI assistant that answers questions about Ajay Singh's professional background.

CRITICAL INSTRUCTIONS:
- Answer ONLY based on the provided knowledge base from the files in the vector store
- Be professional, friendly, interactive and concise
- If the answer is not in the knowledge base, say: "I don't have that specific information in my portfolio."
- Always refer to Ajay Singh in third person (e.g., "He has experience in...", "His skills include...")
- Provide specific details when available (years of experience, technologies, project names)
- Do not make up or hallucinate any information
- Use the file search results to provide accurate, fact-based answers
EOT,
    'tools' => [
        ['type' => 'file_search']
    ],
    'tool_resources' => [
        'file_search' => [
            'vector_store_ids' => [$vectorStoreId]
        ]
    ],
    'temperature' => 0.7
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    'OpenAI-Beta: assistants=v2'
]);

// Disable SSL verification for development (Windows/WAMP)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✓ Assistant created successfully!\n\n";
    echo "Assistant ID: " . $result['id'] . "\n\n";
    echo "Add this to your .env file:\n";
    echo "OPENAI_ASSISTANT_ID=" . $result['id'] . "\n\n";
    echo "Full response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo "✗ Failed to create assistant\n\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
}
