<?php
/**
 * Simple log reader v2 - searches larger chunk
 * DELETE AFTER USE
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Laravel Error Log v2</h1><pre>";

$logFile = __DIR__ . '/../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found\n";
} else {
    $size = filesize($logFile);
    echo "Log file size: " . number_format($size) . " bytes\n\n";

    // Read last 100KB to find the error message
    $readSize = min($size, 100000);
    $fp = fopen($logFile, 'r');
    fseek($fp, $size - $readSize);
    $content = fread($fp, $readSize);
    fclose($fp);

    // Search for the LAST occurrence of .ERROR: which contains the actual message
    $lastPos = strrpos($content, '.ERROR:');

    if ($lastPos !== false) {
        // Find the start of that line (go back to find [ date)
        $lineStart = strrpos(substr($content, 0, $lastPos), "\n");
        if ($lineStart === false)
            $lineStart = 0;

        // Extract the error message line (first 500 chars from that position)
        $errorLine = substr($content, $lineStart, 500);
        echo "=== LAST ERROR MESSAGE ===\n";
        echo htmlspecialchars(trim($errorLine));
        echo "\n\n";

        // Also show from error message to +1000 chars for context
        $errorContext = substr($content, $lineStart, 2000);
        echo "=== ERROR WITH CONTEXT ===\n";
        echo htmlspecialchars(trim($errorContext));
    } else {
        echo "No .ERROR: pattern found in last 100KB.\n\n";

        // Try searching for common error patterns
        $patterns = ['Exception', 'Vite manifest', 'not found', 'does not exist', 'Class', 'Target'];
        foreach ($patterns as $pat) {
            $pos = strrpos($content, $pat);
            if ($pos !== false) {
                $start = max(0, $pos - 100);
                $excerpt = substr($content, $start, 300);
                echo "Found '$pat' at position $pos:\n";
                echo htmlspecialchars($excerpt) . "\n\n";
            }
        }

        // Show content around the middle of what we read (likely near error msg)
        echo "=== CONTENT FROM START OF READ (first 2000 chars) ===\n";
        echo htmlspecialchars(substr($content, 0, 2000));
    }
}

echo "</pre><hr><b>DELETE THIS FILE!</b>";
