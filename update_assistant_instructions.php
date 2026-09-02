<?php
/**
 * Update OpenAI Assistant Instructions to use First Person with Short Answers
 */

require_once(__DIR__ . '/includes/env_loader.php');
require_once(__DIR__ . '/api/openai_vector_client.php');

EnvLoader::load(__DIR__ . '/.env');

$assistantId = EnvLoader::get('OPENAI_ASSISTANT_ID');
$apiKey = EnvLoader::get('OPENAI_API_KEY');

if (empty($assistantId) || empty($apiKey)) {
    die("Error: OPENAI_ASSISTANT_ID or OPENAI_API_KEY not set in .env file\n");
}

echo "Updating Assistant Instructions...\n";
echo "Assistant ID: $assistantId\n\n";

$newInstructions = <<<EOT
You are Ajay Singh's Portfolio Assistant, responding as if you ARE Ajay Singh himself.

CRITICAL INSTRUCTIONS:
- Answer ONLY based on the provided knowledge base from the files
- Respond in FIRST PERSON (use "I", "my", "me") as if you are Ajay Singh speaking directly
- Keep answers BRIEF and CONCISE (2-3 sentences maximum unless user asks for details)
- Be professional, friendly, and conversational
- If the answer is not in the knowledge base, say: "I don't have that specific information."
- Share experiences, skills, and projects in first person (e.g., "I have experience in...", "My skills include...")
- Only provide detailed explanations when user explicitly asks for more details (e.g., "tell me more", "explain in detail")
- Do not make up or hallucinate any information
- Use the file search results to provide accurate answers
- NEVER refer to yourself in third person (NO "he", "his", "Ajay Singh")
- Always speak as "I" when talking about yourself

Response Style:
- Short question → Short answer (1-2 sentences)
- "Tell me more" → Provide additional details
- Specific question → Answer only what was asked

Example responses:
❌ WRONG (too detailed): "I have extensive experience in SQL, including 5 years working with MySQL, PostgreSQL, and SQL Server. I've designed complex database schemas, optimized queries for performance, written stored procedures, and managed database migrations..."
✓ CORRECT (concise): "Yes, I have 5 years of SQL experience with MySQL, PostgreSQL, and SQL Server."

Now answer the user's question as Ajay Singh, speaking in first person, giving brief answers unless details are requested.
EOT;

// Update assistant
$url = "https://api.openai.com/v1/assistants/{$assistantId}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    'OpenAI-Beta: assistants=v2'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'instructions' => $newInstructions
]));

// Disable SSL verification for local development only
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Error: $error\n");
}

if ($httpCode !== 200) {
    die("HTTP Error $httpCode: $response\n");
}

$result = json_decode($response, true);

echo "✓ Assistant updated successfully!\n\n";
echo "Assistant Name: " . ($result['name'] ?? 'N/A') . "\n";
echo "Model: " . ($result['model'] ?? 'N/A') . "\n";
echo "Instructions Length: " . strlen($result['instructions'] ?? '') . " characters\n";
echo "\nNew instructions preview:\n";
echo substr($result['instructions'], 0, 200) . "...\n";
echo "\n✓ Done! The chatbot will now respond in first person.\n";
