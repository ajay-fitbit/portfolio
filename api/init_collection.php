<?php
/**
 * Initialize Chroma Collection
 * Run this once to set up the portfolio_kb collection
 */

require_once(__DIR__ . '/chroma_client.php');

try {
    $client = new ChromaClient();
    
    // Check if Chroma server is running
    if (!$client->isServerRunning()) {
        die("ERROR: Chroma server is not running. Please start it using start_chroma.bat\n");
    }
    
    echo "Chroma server is running!\n";
    echo "Creating portfolio_kb collection...\n";
    
    // Try to create the collection
    try {
        $result = $client->createCollection();
        echo "✓ Collection created successfully!\n";
        print_r($result);
    } catch (Exception $e) {
        // Collection might already exist
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ Collection already exists!\n";
        } else {
            throw $e;
        }
    }
    
    // Get collection info
    echo "\nCollection Info:\n";
    $info = $client->getCollectionInfo();
    print_r($info);
    
    echo "\n✓ Initialization complete!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
