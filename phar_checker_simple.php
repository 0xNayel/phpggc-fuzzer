#!/usr/bin/env php
<?php
/**
 * PHAR Gadget Checker - Simple PHP Version
 * 
 * Quick and simple PHAR tester that stops when /tmp/poc is created
 * 
 * Usage: php phar_checker_simple.php <phar_directory>
 */

$POC_FILE = '/tmp/poc';

// Colors
function color($text, $color) {
    $colors = [
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'cyan' => "\033[0;36m",
        'nc' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['nc'];
}

// Check arguments
if ($argc < 2) {
    echo "Usage: php " . basename($argv[0]) . " <phar_directory>\n";
    echo "Example: php " . basename($argv[0]) . " ./phar_gadgets\n\n";
    exit(1);
}

$directory = $argv[1];

if (!is_dir($directory)) {
    echo color("[-] Directory not found: $directory\n", 'red');
    exit(1);
}

echo color("╔════════════════════════════════════════╗\n", 'green');
echo color("║     PHAR Checker - Simple Version     ║\n", 'green');
echo color("╚════════════════════════════════════════╝\n", 'green');
echo "\n";
echo color("[*] Directory: $directory\n", 'blue');
echo color("[*] POC File: $POC_FILE\n", 'blue');
echo "\n";

// Find all PHAR files
echo color("[*] Scanning for PHAR files...\n", 'yellow');
$pharFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'phar') {
        $pharFiles[] = $file->getPathname();
    }
}

sort($pharFiles);
$total = count($pharFiles);

if ($total === 0) {
    echo color("[-] No PHAR files found\n", 'red');
    exit(1);
}

echo color("[+] Found $total PHAR files\n\n", 'green');

// Test each PHAR
$tested = 0;
foreach ($pharFiles as $pharPath) {
    $tested++;
    $pharName = basename($pharPath);
    
    echo color("[$tested/$total] Testing: $pharName\n", 'yellow');
    
    // Remove POC file if exists
    if (file_exists($POC_FILE)) {
        @unlink($POC_FILE);
    }
    
    // Test with multiple methods
    $methods = [
        'file_get_contents' => function($p) { @file_get_contents("phar://$p/test.txt"); },
        'file_exists' => function($p) { @file_exists("phar://$p/test.txt"); },
        'fopen' => function($p) { $f = @fopen("phar://$p/test.txt", "r"); if($f) fclose($f); },
        'is_file' => function($p) { @is_file("phar://$p/test.txt"); },
    ];
    
    $success = false;
    $successMethod = '';
    
    foreach ($methods as $methodName => $callback) {
        try {
            $callback($pharPath);
            usleep(300000); // 0.3 seconds
            
            if (file_exists($POC_FILE)) {
                $success = true;
                $successMethod = $methodName;
                break;
            }
        } catch (Exception $e) {
            // Continue to next method
        } catch (Error $e) {
            // Continue to next method
        }
    }
    
    if ($success) {
        echo "\n";
        echo color("╔════════════════════════════════════════╗\n", 'green');
        echo color("║          SUCCESS FOUND!                ║\n", 'green');
        echo color("╚════════════════════════════════════════╝\n", 'green');
        echo color("[✓] VULNERABLE PHAR: $pharName\n", 'green');
        echo color("[✓] Full Path: $pharPath\n", 'green');
        echo color("[✓] Method: $successMethod\n", 'green');
        echo color("[✓] POC File: $POC_FILE created\n", 'green');
        echo "\n";
        echo color("[!] Stopping test - vulnerable gadget found!\n", 'yellow');
        exit(0);
    } else {
        echo color("  [✗] No POC created\n", 'red');
    }
    
    echo "\n";
}

// No success
echo color("╔════════════════════════════════════════╗\n", 'yellow');
echo color("║      All Tests Complete - No Success   ║\n", 'yellow');
echo color("╚════════════════════════════════════════╝\n", 'yellow');
echo color("[*] Tested $tested PHARs - none created /tmp/poc\n", 'yellow');
exit(1);
?>
