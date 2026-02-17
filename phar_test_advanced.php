<?php
/**
 * PHAR Deserialization Test Harness - Advanced
 * 
 * Usage: php phar_test_advanced.php <phar_file> [method]
 * 
 * Methods:
 *   1 - file_get_contents (phar://)
 *   2 - file_exists (phar://)
 *   3 - fopen (phar://)
 *   4 - stat/is_file (phar://)
 *   5 - include/require (phar://)
 *   all - Try all methods
 */

if ($argc < 2) {
    echo "Usage: php {$argv[0]} <phar_file> [method]\n";
    echo "\nMethods:\n";
    echo "  1   - file_get_contents (phar://)\n";
    echo "  2   - file_exists (phar://)\n";
    echo "  3   - fopen (phar://)\n";
    echo "  4   - stat/is_file (phar://)\n";
    echo "  5   - include (phar://)\n";
    echo "  all - Try all methods (default)\n";
    echo "\nExample: php {$argv[0]} gadget.phar 1\n";
    exit(1);
}

$phar_file = $argv[1];
$method = isset($argv[2]) ? $argv[2] : 'all';

if (!file_exists($phar_file)) {
    echo "[-] File not found: $phar_file\n";
    exit(1);
}

// Get absolute path
$phar_file = realpath($phar_file);

echo "[*] Testing PHAR: $phar_file\n";
echo "[*] Method: $method\n";
echo "[*] Starting deserialization tests...\n\n";

function test_method($name, $phar_path, $callback) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "[*] Method: $name\n";
    
    try {
        $callback($phar_path);
        echo "[+] Method executed successfully\n";
    } catch (Exception $e) {
        echo "[-] Exception: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "[-] Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Method 1: file_get_contents
if ($method == '1' || $method == 'all') {
    test_method('file_get_contents(phar://)', $phar_file, function($path) {
        @file_get_contents("phar://" . $path . "/test.txt");
    });
}

// Method 2: file_exists
if ($method == '2' || $method == 'all') {
    test_method('file_exists(phar://)', $phar_file, function($path) {
        @file_exists("phar://" . $path . "/test.txt");
    });
}

// Method 3: fopen
if ($method == '3' || $method == 'all') {
    test_method('fopen(phar://)', $phar_file, function($path) {
        $fp = @fopen("phar://" . $path . "/test.txt", "r");
        if ($fp) fclose($fp);
    });
}

// Method 4: stat/is_file
if ($method == '4' || $method == 'all') {
    test_method('is_file(phar://)', $phar_file, function($path) {
        @is_file("phar://" . $path . "/test.txt");
    });
    
    test_method('stat(phar://)', $phar_file, function($path) {
        @stat("phar://" . $path . "/test.txt");
    });
}

// Method 5: include
if ($method == '5' || $method == 'all') {
    test_method('include(phar://)', $phar_file, function($path) {
        @include("phar://" . $path . "/test.txt");
    });
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[*] All tests completed\n";
echo "[*] Check /tmp/poc for successful exploitation\n";
?>
