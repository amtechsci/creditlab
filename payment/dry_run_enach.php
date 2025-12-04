<?php
// Dry run endpoint for E-NACH auto debit
// This file executes auto_enach.php in dry run mode and returns the output

// Set output buffering to capture all output
ob_start();

// Set dry_run flag
$_GET['dry_run'] = '1';

// Read the auto_enach.php file and execute it
$auto_enach_file = __DIR__ . '/auto_enach.php';
if (file_exists($auto_enach_file)) {
    // Read the file content
    $file_content = file_get_contents($auto_enach_file);
    
    // Remove HTML comment tags around PHP code
    // Remove opening <!-- at the start (with optional whitespace)
    $file_content = preg_replace('/^<!--\s*/', '', $file_content);
    // Remove closing --> at the end (with optional whitespace and newline)
    $file_content = preg_replace('/\s*-->[\r\n]*$/', '', $file_content);
    
    // Create a temporary file to execute
    $temp_file = tempnam(sys_get_temp_dir(), 'enach_dry_run_');
    file_put_contents($temp_file, $file_content);
    
    // Include the temporary file
    include $temp_file;
    
    // Clean up temporary file
    unlink($temp_file);
} else {
    echo "Error: auto_enach.php file not found at: $auto_enach_file\n";
}

// Get the output
$output = ob_get_clean();

// Return the output
header('Content-Type: text/plain; charset=utf-8');
echo $output;
?>

