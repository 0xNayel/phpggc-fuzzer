#!/usr/bin/env php
<?php
/**
 * PHAR Gadget Checker - Custom Callback Version
 * 
 * Allows you to define custom deserialization code for testing
 * Useful when you know the specific vulnerable pattern in your target app
 * 
 * Usage: Modify the customDeserialize() function below, then run:
 *        php phar_checker_custom.php <phar_directory>
 */

$POC_FILE = '/tmp/poc';

/**
 * CUSTOMIZE THIS FUNCTION
 * 
 * This is where you put your custom deserialization code
 * that mimics how your target application processes PHAR files
 * 
 * Examples:
 * 1. Direct deserialization:
 *    unserialize(file_get_contents($pharPath));
 * 
 * 2. PHAR stream wrapper:
 *    file_get_contents("phar://$pharPath/anything.txt");
 * 
 * 3. Custom app logic:
 *    $data = file_get_contents($pharPath);
 *    YourVulnerableClass::process($data);
 */
function customDeserialize($pharPath) {
    // DEFAULT: Try common PHAR deserialization patterns
    
    // Method 1: PHAR stream wrapper with file_get_contents
    @file_get_contents("phar://$pharPath/test.txt");
    
    // Method 2: PHAR stream wrapper with file_exists
    @file_exists("phar://$pharPath/test.txt");
    
    // Method 3: PHAR stream wrapper with fopen
    $fp = @fopen("phar://$pharPath/test.txt", "r");
    if ($fp) fclose($fp);
    
    // ADD YOUR CUSTOM DESERIALIZATION CODE HERE:
    // Example for a specific application:
    /*
    try {
        // If your app does this:
        $phar = new Phar($pharPath);
        $metadata = $phar->getMetadata();
        
        // Or if your app does this:
        include("phar://$pharPath/index.php");
        
        // Or if your app deserializes uploaded files:
        $content = file_get_contents($pharPath);
        $obj = unserialize($content);
    } catch (Exception $e) {
        // Ignore errors
    }
    */
}

// ============================================================================
// You shouldn't need to modify anything below this line
// ============================================================================

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

if ($argc < 2) {
    echo "Usage: php " . basename($argv[0]) . " <phar_directory>\n";
    echo "\nBefore running, customize the customDeserialize() function\n";
    echo "to match your target application's deserialization pattern.\n\n";
    echo "Example: php " . basename($argv[0]) . " ./phar_gadgets\n\n";
    exit(1);
}

$directory = $argv[1];

if (!is_dir($directory)) {
    echo color("[-] Directory not found: $directory\n", 'red');
    exit(1);
}

echo color("╔════════════════════════════════════════╗\n", 'green');
echo color("║     PHAR Checker - Custom Version     ║\n", 'green');
echo color("╚════════════════════════════════════════╝\n", 'green');
echo "\n";
echo color("[*] Directory: $directory\n", 'blue');
echo color("[*] POC File: $POC_FILE\n", 'blue');
echo color("[*] Using custom deserialization function\n", 'blue');
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
$startTime = microtime(true);

foreach ($pharFiles as $pharPath) {
    $tested++;
    $pharName = basename($pharPath);
    
    echo color("[$tested/$total] Testing: $pharName\n", 'yellow');
    
    // Remove POC file if exists
    if (file_exists($POC_FILE)) {
        @unlink($POC_FILE);
    }
    
    // Execute custom deserialization
    try {
        customDeserialize($pharPath);
    } catch (Exception $e) {
        echo color("  [!] Exception: " . $e->getMessage() . "\n", 'cyan');
    } catch (Error $e) {
        echo color("  [!] Error: " . $e->getMessage() . "\n", 'cyan');
    }
    
    // Wait a bit for file creation
    usleep(500000); // 0.5 seconds
    
    // Check if POC was created
    if (file_exists($POC_FILE)) {
        $duration = round(microtime(true) - $startTime, 2);
        
        echo "\n";
        echo color("╔════════════════════════════════════════╗\n", 'green');
        echo color("║       VULNERABLE GADGET FOUND!         ║\n", 'green');
        echo color("╚════════════════════════════════════════╝\n", 'green');
        echo color("[✓] SUCCESS!\n", 'green');
        echo color("[✓] PHAR File: $pharName\n", 'green');
        echo color("[✓] Full Path: $pharPath\n", 'green');
        echo color("[✓] POC Created: $POC_FILE\n", 'green');
        echo color("[✓] Time: $duration seconds\n", 'green');
        echo color("[✓] Tested: $tested/$total PHARs\n", 'green');
        echo "\n";
        
        // Save result to file
        $resultFile = 'phar_success_' . date('Ymd_His') . '.txt';
        $resultContent = "VULNERABLE PHAR FOUND\n";
        $resultContent .= str_repeat("=", 50) . "\n";
        $resultContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $resultContent .= "PHAR File: $pharName\n";
        $resultContent .= "Full Path: $pharPath\n";
        $resultContent .= "POC File: $POC_FILE\n";
        $resultContent .= "Tested: $tested/$total PHARs\n";
        $resultContent .= "Duration: $duration seconds\n";
        file_put_contents($resultFile, $resultContent);
        
        echo color("[*] Result saved to: $resultFile\n", 'blue');
        echo color("[!] Stopping test - vulnerable gadget found!\n", 'yellow');
        
        exit(0); // Success
    } else {
        echo color("  [✗] POC not created\n", 'red');
    }
    
    echo "\n";
}

// No success
$duration = round(microtime(true) - $startTime, 2);

echo color("╔════════════════════════════════════════╗\n", 'yellow');
echo color("║      All Tests Completed               ║\n", 'yellow');
echo color("╚════════════════════════════════════════╝\n", 'yellow');
echo color("[*] Tested: $tested PHARs\n", 'yellow');
echo color("[*] No vulnerable gadgets found\n", 'yellow');
echo color("[*] Duration: $duration seconds\n", 'yellow');

exit(1); // No success
?>
