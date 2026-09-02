<?php
/**
 * Knowledge Ingestion Endpoint
 * Processes resume files and stores them in OpenAI Vector Store
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../includes/env_loader.php');
require_once(__DIR__ . '/openai_vector_client.php');

// Load environment variables
EnvLoader::load(__DIR__ . '/../.env');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

try {
    $client = new OpenAIVectorClient();
    
    // Check if OpenAI Vector Store is configured
    if (empty(EnvLoader::get('OPENAI_VECTOR_STORE_ID'))) {
        throw new Exception('OpenAI Vector Store is not configured. Please set OPENAI_VECTOR_STORE_ID in .env file');
    }
    
    // Check if Vector Store is accessible
    if (!$client->isServerRunning()) {
        throw new Exception('Cannot connect to OpenAI Vector Store. Please check your configuration.');
    }
    
    // Parse JSON input if Content-Type is application/json
    $input = [];
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $input = json_decode($json, true) ?? [];
    }
    
    $response = ['status' => 'error', 'message' => 'Invalid request'];
    
    // Handle different actions - check both POST and parsed JSON input
    $action = $_POST['action'] ?? $input['action'] ?? $_GET['action'] ?? 'ingest_all';
    
    switch ($action) {
        case 'ingest_all':
            $response = ingestAllResumes($client);
            break;
            
        case 'ingest_file':
            $filename = $_POST['filename'] ?? $input['filename'] ?? null;
            if ($filename) {
                $response = ingestSingleFile($client, $filename);
            } else {
                $response = ['status' => 'error', 'message' => 'Filename not provided'];
            }
            break;
            
        case 'ingest_text':
            $text = $_POST['text'] ?? $input['text'] ?? null;
            $source = $_POST['source'] ?? $input['source'] ?? 'manual';
            if ($text) {
                $response = ingestText($client, $text, $source);
            } else {
                $response = ['status' => 'error', 'message' => 'Text not provided'];
            }
            break;
            
        case 'status':
            $response = getIngestionStatus($client);
            break;
            
        case 'reset':
            $response = resetKnowledgeBase($client);
            break;
            
        default:
            $response = ['status' => 'error', 'message' => 'Unknown action'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Ingest all resume files from the resume directory
 */
function ingestAllResumes($client) {
    $resumeDir = __DIR__ . '/../resume/';
    $files = scandir($resumeDir);
    $ingestedCount = 0;
    $errors = [];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'doc', 'docx', 'txt', 'md'])) continue;
        
        try {
            $result = ingestSingleFile($client, $file);
            if ($result['status'] === 'success') {
                $ingestedCount++;
            }
        } catch (Exception $e) {
            $errors[] = "Error processing $file: " . $e->getMessage();
        }
    }
    
    return [
        'status' => 'success',
        'files_uploaded' => $ingestedCount,
        'message' => "Successfully uploaded {$ingestedCount} files to Vector Store",
        'errors' => $errors
    ];
}

/**
 * Ingest a single file
 */
function ingestSingleFile($client, $filename) {
    $resumeDir = __DIR__ . '/../resume/';
    $filepath = $resumeDir . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception("File not found: $filename");
    }
    
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // OpenAI Vector Store supports direct file upload
    // Supported types: pdf, doc, docx, txt, md
    if (!in_array($extension, ['pdf', 'doc', 'docx', 'txt', 'md'])) {
        throw new Exception("Unsupported file type: $extension");
    }
    
    try {
        // Upload file directly to Vector Store
        $result = $client->uploadFile($filepath, 'assistants');
        
        return [
            'status' => 'success',
            'message' => "File uploaded successfully: $filename",
            'file_id' => $result['id'] ?? null
        ];
    } catch (Exception $e) {
        throw new Exception("Failed to upload $filename: " . $e->getMessage());
    }
}

/**
 * Ingest raw text - NOT USED with OpenAI Vector Store
 * OpenAI handles text extraction and chunking automatically
 */
function ingestText($client, $text, $source = 'manual') {
    // For OpenAI Vector Store, create a temporary file and upload it
    $tempFile = sys_get_temp_dir() . '/temp_' . time() . '.txt';
    file_put_contents($tempFile, $text);
    
    try {
        $result = $client->uploadFile($tempFile, 'assistants');
        unlink($tempFile); // Clean up temp file
        
        return [
            'status' => 'success',
            'message' => 'Text ingested successfully',
            'file_id' => $result['id'] ?? null
        ];
    } catch (Exception $e) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        throw $e;
    }
}

/**
 * Get ingestion status
 */
function getIngestionStatus($client) {
    try {
        $info = $client->getVectorStoreInfo();
        $count = $client->getDocumentCount();
        
        return [
            'status' => 'success',
            'collection' => $info['name'] ?? 'OpenAI Vector Store',
            'document_count' => $count,
            'vector_store_id' => $info['id'] ?? 'unknown',
            'metadata' => [
                'status' => $info['status'] ?? 'unknown',
                'usage_bytes' => $info['usage_bytes'] ?? 0,
                'created_at' => $info['created_at'] ?? null
            ]
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Reset knowledge base (delete all files from Vector Store)
 */
function resetKnowledgeBase($client) {
    try {
        $files = $client->listFiles();
        $deletedCount = 0;
        
        if (isset($files['data']) && is_array($files['data'])) {
            foreach ($files['data'] as $file) {
                try {
                    $client->deleteFile($file['id']);
                    $deletedCount++;
                } catch (Exception $e) {
                    // Continue even if one file fails
                }
            }
        }
        
        return [
            'status' => 'success',
            'message' => "Knowledge base reset successfully. Deleted {$deletedCount} files."
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Extract text from PDF (using Python PyPDF2)
 */
function extractTextFromPDF($filepath) {
    $pythonScript = __DIR__ . '/extract_text.py';
    // Try to find Python executable
    $pythonCmd = findPythonCommand();
    $command = "$pythonCmd \"$pythonScript\" \"$filepath\" 2>&1";
    
    exec($command, $output, $returnVar);
    $text = implode("\n", $output);
    
    if ($returnVar !== 0 || strpos($text, 'ERROR:') === 0) {
        throw new Exception("Unable to extract text from PDF: " . $text);
    }
    
    return $text;
}

/**
 * Find Python command
 */
function findPythonCommand() {
    // Try common Python paths
    $pythonPaths = [
        'C:\\Users\\Ajay\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
        'C:\\Python311\\python.exe',
        'python',
        'python3',
        'py'
    ];
    
    foreach ($pythonPaths as $path) {
        exec("$path --version 2>&1", $output, $returnVar);
        if ($returnVar === 0) {
            return $path;
        }
    }
    
    return 'python'; // Fallback
}

/**
 * Extract text from DOC/DOCX files
 */
function extractTextFromDoc($filepath) {
    $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    // Use Python script for DOCX extraction
    if ($extension === 'docx') {
        $pythonScript = __DIR__ . '/extract_text.py';
        $pythonCmd = findPythonCommand();
        $command = "$pythonCmd \"$pythonScript\" \"$filepath\" 2>&1";
        
        exec($command, $output, $returnVar);
        $text = implode("\n", $output);
        
        if ($returnVar !== 0 || strpos($text, 'ERROR:') === 0) {
            throw new Exception("Unable to extract text from DOCX: " . $text);
        }
        
        return $text;
    }
    
    // For old .doc files, suggest conversion
    throw new Exception("Old .doc format not supported. Please convert to .docx or .pdf format.");
}
?>
