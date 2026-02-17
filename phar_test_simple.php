<?php
/**
 * PHAR Deserialization Test Harness - Example 1 (Simple)
 * 
 * Usage: php phar_test_simple.php <phar_file>
 * 
 * This script demonstrates a simple PHAR deserialization vulnerability
 * by directly deserializing a PHAR file.
 */

if ($argc < 2) {
    echo "Usage: php {$argv[0]} <phar_file>\n";
    echo "Example: php {$argv[0]} gadget.phar\n";
    exit(1);
}

$phar_file = $argv[1];

if (!file_exists($phar_file)) {
    echo "[-] File not found: $phar_file\n";
    exit(1);
}

echo "[*] Testing PHAR: $phar_file\n";
echo "[*] Reading PHAR metadata...\n";

try {
    // Method 1: Direct file operations trigger deserialization
    $data = file_get_contents("phar://" . $phar_file . "/test.txt");
    
    echo "[*] PHAR processed\n";
    
} catch (Exception $e) {
    echo "[-] Exception: " . $e->getMessage() . "\n";
}

echo "[*] Test complete\n";
?>
