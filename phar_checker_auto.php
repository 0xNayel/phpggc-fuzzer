#!/usr/bin/env php
<?php
/**
 * PHAR Gadget Chain Checker - PHP Version
 * 
 * Automatically tests all PHAR files in a directory for deserialization vulnerabilities
 * Stops when /tmp/poc file is created and reports the successful gadget
 * 
 * Usage: php phar_checker_auto.php <phar_directory> [method]
 * 
 * Methods:
 *   all       - Try all deserialization methods (default)
 *   file_get  - file_get_contents(phar://)
 *   file_exists - file_exists(phar://)
 *   fopen     - fopen(phar://)
 *   stat      - stat/is_file(phar://)
 *   include   - include(phar://)
 */

// ANSI Colors
class Colors {
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const CYAN = "\033[0;36m";
    const NC = "\033[0m";
}

class PharChecker {
    private $pocFile = '/tmp/poc';
    private $resultsFile;
    private $testedCount = 0;
    private $successCount = 0;
    private $failedCount = 0;
    private $method = 'all';
    private $startTime;
    
    public function __construct($method = 'all') {
        $this->method = $method;
        $this->resultsFile = 'phar_test_results_' . date('Ymd_His') . '.txt';
        $this->startTime = microtime(true);
    }
    
    /**
     * Print colored message
     */
    private function print($message, $color = Colors::NC) {
        echo $color . $message . Colors::NC . "\n";
    }
    
    /**
     * Print header
     */
    public function printHeader() {
        $this->print("╔════════════════════════════════════════╗", Colors::GREEN);
        $this->print("║   PHAR Gadget Checker - PHP Version   ║", Colors::GREEN);
        $this->print("╚════════════════════════════════════════╝", Colors::GREEN);
        echo "\n";
    }
    
    /**
     * Find all PHAR files in directory
     */
    public function findPharFiles($directory) {
        if (!is_dir($directory)) {
            $this->print("[-] Directory not found: $directory", Colors::RED);
            exit(1);
        }
        
        $pharFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'phar') {
                $pharFiles[] = $file->getPathname();
            }
        }
        
        sort($pharFiles);
        return $pharFiles;
    }
    
    /**
     * Clean up POC file
     */
    private function cleanupPoc() {
        if (file_exists($this->pocFile)) {
            @unlink($this->pocFile);
        }
    }
    
    /**
     * Check if POC file was created
     */
    private function checkPoc() {
        // Small delay to ensure file creation
        usleep(500000); // 0.5 seconds
        return file_exists($this->pocFile);
    }
    
    /**
     * Test PHAR with file_get_contents
     */
    private function testFileGetContents($pharPath) {
        try {
            @file_get_contents("phar://" . $pharPath . "/test.txt");
            return true;
        } catch (Exception $e) {
            return false;
        } catch (Error $e) {
            return false;
        }
    }
    
    /**
     * Test PHAR with file_exists
     */
    private function testFileExists($pharPath) {
        try {
            @file_exists("phar://" . $pharPath . "/test.txt");
            return true;
        } catch (Exception $e) {
            return false;
        } catch (Error $e) {
            return false;
        }
    }
    
    /**
     * Test PHAR with fopen
     */
    private function testFopen($pharPath) {
        try {
            $fp = @fopen("phar://" . $pharPath . "/test.txt", "r");
            if ($fp) {
                fclose($fp);
            }
            return true;
        } catch (Exception $e) {
            return false;
        } catch (Error $e) {
            return false;
        }
    }
    
    /**
     * Test PHAR with stat/is_file
     */
    private function testStat($pharPath) {
        try {
            @is_file("phar://" . $pharPath . "/test.txt");
            @stat("phar://" . $pharPath . "/test.txt");
            return true;
        } catch (Exception $e) {
            return false;
        } catch (Error $e) {
            return false;
        }
    }
    
    /**
     * Test PHAR with include
     */
    private function testInclude($pharPath) {
        try {
            @include("phar://" . $pharPath . "/test.txt");
            return true;
        } catch (Exception $e) {
            return false;
        } catch (Error $e) {
            return false;
        }
    }
    
    /**
     * Test PHAR with all methods
     */
    public function testPhar($pharPath) {
        $pharName = basename($pharPath);
        $methods = [];
        
        switch ($this->method) {
            case 'file_get':
                $methods = ['file_get_contents' => [$this, 'testFileGetContents']];
                break;
            case 'file_exists':
                $methods = ['file_exists' => [$this, 'testFileExists']];
                break;
            case 'fopen':
                $methods = ['fopen' => [$this, 'testFopen']];
                break;
            case 'stat':
                $methods = ['stat/is_file' => [$this, 'testStat']];
                break;
            case 'include':
                $methods = ['include' => [$this, 'testInclude']];
                break;
            case 'all':
            default:
                $methods = [
                    'file_get_contents' => [$this, 'testFileGetContents'],
                    'file_exists' => [$this, 'testFileExists'],
                    'fopen' => [$this, 'testFopen'],
                    'stat/is_file' => [$this, 'testStat'],
                    'include' => [$this, 'testInclude'],
                ];
                break;
        }
        
        foreach ($methods as $methodName => $callback) {
            $this->cleanupPoc();
            
            $this->print("  [*] Testing with: $methodName", Colors::BLUE);
            
            // Execute the test
            $executed = call_user_func($callback, $pharPath);
            
            // Check if POC was created
            if ($this->checkPoc()) {
                $this->print("  [✓] SUCCESS! /tmp/poc was created", Colors::GREEN);
                $this->print("  [!] Method: $methodName", Colors::GREEN);
                return [
                    'success' => true,
                    'method' => $methodName,
                    'phar' => $pharName,
                    'path' => $pharPath
                ];
            }
        }
        
        return ['success' => false];
    }
    
    /**
     * Log result to file
     */
    private function logResult($result) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] %s - %s\n",
            $timestamp,
            $result['success'] ? 'SUCCESS' : 'FAILED',
            $result['phar']
        );
        
        if ($result['success']) {
            $logEntry .= sprintf("  Method: %s\n  Path: %s\n\n", 
                $result['method'], $result['path']);
        }
        
        file_put_contents($this->resultsFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Run the checker
     */
    public function run($directory) {
        $this->printHeader();
        
        $this->print("[*] PHAR Directory: $directory", Colors::BLUE);
        $this->print("[*] Results File: {$this->resultsFile}", Colors::BLUE);
        $this->print("[*] Indicator File: {$this->pocFile}", Colors::BLUE);
        $this->print("[*] Test Method: {$this->method}", Colors::BLUE);
        echo "\n";
        
        // Initialize results file
        $header = "PHAR Gadget Chain Test Results\n";
        $header .= str_repeat("=", 50) . "\n";
        $header .= "Test Date: " . date('Y-m-d H:i:s') . "\n";
        $header .= "Directory: $directory\n";
        $header .= "Method: {$this->method}\n\n";
        file_put_contents($this->resultsFile, $header);
        
        // Find all PHAR files
        $this->print("[*] Scanning for PHAR files...", Colors::YELLOW);
        $pharFiles = $this->findPharFiles($directory);
        $total = count($pharFiles);
        
        if ($total === 0) {
            $this->print("[-] No PHAR files found in $directory", Colors::RED);
            exit(1);
        }
        
        $this->print("[+] Found $total PHAR files to test", Colors::GREEN);
        echo "\n";
        
        // Test each PHAR
        foreach ($pharFiles as $index => $pharPath) {
            $this->testedCount++;
            $current = $index + 1;
            $pharName = basename($pharPath);
            
            $this->print("[$current/$total] Testing: $pharName", Colors::YELLOW);
            $this->print("  Path: $pharPath", Colors::CYAN);
            
            $result = $this->testPhar($pharPath);
            $result['phar'] = $pharName;
            $result['path'] = $pharPath;
            
            $this->logResult($result);
            
            if ($result['success']) {
                $this->successCount++;
                
                echo "\n";
                $this->print("╔════════════════════════════════════════╗", Colors::GREEN);
                $this->print("║        VULNERABLE GADGET FOUND!        ║", Colors::GREEN);
                $this->print("╚════════════════════════════════════════╝", Colors::GREEN);
                $this->print("[!] File: {$result['phar']}", Colors::GREEN);
                $this->print("[!] Path: {$result['path']}", Colors::GREEN);
                $this->print("[!] Method: {$result['method']}", Colors::GREEN);
                $this->print("[!] POC File: {$this->pocFile}", Colors::GREEN);
                echo "\n";
                
                // Save final summary
                $this->saveSummary($total, $result);
                
                // Stop testing
                $this->print("[*] Stopping test (successful gadget found)", Colors::YELLOW);
                return $result;
            } else {
                $this->failedCount++;
                $this->print("  [✗] Failed - /tmp/poc not created", Colors::RED);
            }
            
            echo "\n";
        }
        
        // All tests completed without success
        $this->print("╔════════════════════════════════════════╗", Colors::YELLOW);
        $this->print("║       All Tests Completed              ║", Colors::YELLOW);
        $this->print("╚════════════════════════════════════════╝", Colors::YELLOW);
        $this->print("[*] No vulnerable gadgets found", Colors::YELLOW);
        $this->saveSummary($total, null);
        
        return null;
    }
    
    /**
     * Save summary to results file
     */
    private function saveSummary($total, $successResult) {
        $duration = microtime(true) - $this->startTime;
        
        $summary = "\n" . str_repeat("=", 50) . "\n";
        $summary .= "Summary\n";
        $summary .= str_repeat("=", 50) . "\n";
        $summary .= "Total PHARs: $total\n";
        $summary .= "Tested: {$this->testedCount}\n";
        $summary .= "Successful: {$this->successCount}\n";
        $summary .= "Failed: {$this->failedCount}\n";
        $summary .= "Duration: " . round($duration, 2) . " seconds\n\n";
        
        if ($successResult) {
            $summary .= "Vulnerable Gadget:\n";
            $summary .= "  File: {$successResult['phar']}\n";
            $summary .= "  Path: {$successResult['path']}\n";
            $summary .= "  Method: {$successResult['method']}\n";
        }
        
        file_put_contents($this->resultsFile, $summary, FILE_APPEND);
        
        // Print summary
        echo "\n";
        $this->print("Summary:", Colors::CYAN);
        $this->print("  Total PHARs: $total", Colors::NC);
        $this->print("  Tested: {$this->testedCount}", Colors::NC);
        $this->print("  Successful: {$this->successCount}", Colors::GREEN);
        $this->print("  Failed: {$this->failedCount}", Colors::RED);
        $this->print("  Duration: " . round($duration, 2) . " seconds", Colors::NC);
        echo "\n";
        $this->print("[*] Results saved to: {$this->resultsFile}", Colors::BLUE);
    }
}

// Main execution
if ($argc < 2) {
    echo "Usage: php " . basename($argv[0]) . " <phar_directory> [method]\n\n";
    echo "Methods:\n";
    echo "  all         - Try all deserialization methods (default)\n";
    echo "  file_get    - file_get_contents(phar://)\n";
    echo "  file_exists - file_exists(phar://)\n";
    echo "  fopen       - fopen(phar://)\n";
    echo "  stat        - stat/is_file(phar://)\n";
    echo "  include     - include(phar://)\n\n";
    echo "Examples:\n";
    echo "  php " . basename($argv[0]) . " ./phar_gadgets\n";
    echo "  php " . basename($argv[0]) . " ./phar_gadgets all\n";
    echo "  php " . basename($argv[0]) . " ./phar_gadgets file_get\n\n";
    exit(1);
}

$directory = $argv[1];
$method = isset($argv[2]) ? $argv[2] : 'all';

$checker = new PharChecker($method);
$result = $checker->run($directory);

if ($result) {
    exit(0); // Success - vulnerable gadget found
} else {
    exit(1); // No vulnerable gadgets found
}
?>
