<?php
// Function to log downloads
function logDownload($file) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_entry = "IP: $ip_address - File: $file - Downloaded on: " . date("Y-m-d H:i:s") . "\n";
    file_put_contents('downloads.log', $log_entry, FILE_APPEND);
}

// Get the file from the query parameter
$file = $_GET['file'];

// Check if the file exists
if (file_exists($file)) {
    // Log the download
    logDownload($file);

    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    echo "File not found.";
}
?>
