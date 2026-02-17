# PHP PHAR Checker - Usage Guide

This guide covers the three PHP-based PHAR checkers for automated testing.

## Available PHP Checkers

### 1. **phar_checker_auto.php** - Full-Featured Automatic Checker
- Complete testing suite with all deserialization methods
- Detailed logging and results
- Method selection (all, file_get, file_exists, fopen, stat, include)
- Best for comprehensive testing

### 2. **phar_checker_simple.php** - Quick and Simple
- Fast testing with common methods
- Minimal output
- Easy to use
- Best for quick scans

### 3. **phar_checker_custom.php** - Customizable Testing
- Define your own deserialization code
- Mimics specific application patterns
- Best when you know the target app's vulnerable code

---

## phar_checker_auto.php - Full Featured

### Usage

```bash
php phar_checker_auto.php <directory> [method]
```

### Methods

- `all` - Try all deserialization methods (default)
- `file_get` - file_get_contents(phar://)
- `file_exists` - file_exists(phar://)
- `fopen` - fopen(phar://)
- `stat` - stat/is_file(phar://)
- `include` - include(phar://)

### Examples

```bash
# Test all methods (default)
php phar_checker_auto.php ./phar_gadgets

# Test with file_get_contents only
php phar_checker_auto.php ./phar_gadgets file_get

# Test with all methods explicitly
php phar_checker_auto.php ./phar_gadgets all
```

### Output

```
╔════════════════════════════════════════╗
║   PHAR Gadget Checker - PHP Version   ║
╚════════════════════════════════════════╝

[*] PHAR Directory: ./phar_gadgets
[*] Results File: phar_test_results_20260217_153022.txt
[*] Indicator File: /tmp/poc
[*] Test Method: all

[*] Scanning for PHAR files...
[+] Found 284 PHAR files to test

[1/284] Testing: Symfony_RCE1_cmd.phar
  Path: ./phar_gadgets/Symfony_RCE1_cmd.phar
  [*] Testing with: file_get_contents
  [*] Testing with: file_exists
  [✓] SUCCESS! /tmp/poc was created
  [!] Method: file_exists

╔════════════════════════════════════════╗
║        VULNERABLE GADGET FOUND!        ║
╚════════════════════════════════════════╝
[!] File: Symfony_RCE1_cmd.phar
[!] Path: ./phar_gadgets/Symfony_RCE1_cmd.phar
[!] Method: file_exists
[!] POC File: /tmp/poc

[*] Stopping test (successful gadget found)
```

### Features

- ✓ Tests multiple deserialization methods per PHAR
- ✓ Detailed logging to timestamped file
- ✓ Stops immediately when /tmp/poc is created
- ✓ Reports exact method that triggered the gadget
- ✓ Full path information for reproduction
- ✓ Summary statistics

---

## phar_checker_simple.php - Quick Testing

### Usage

```bash
php phar_checker_simple.php <directory>
```

### Examples

```bash
php phar_checker_simple.php ./phar_gadgets
```

### Output

```
╔════════════════════════════════════════╗
║     PHAR Checker - Simple Version     ║
╚════════════════════════════════════════╝

[*] Directory: ./phar_gadgets
[*] POC File: /tmp/poc

[*] Scanning for PHAR files...
[+] Found 284 PHAR files

[1/284] Testing: Symfony_RCE1_cmd.phar
  [✗] No POC created

[2/284] Testing: Laravel_RCE1_phpcode.phar

╔════════════════════════════════════════╗
║          SUCCESS FOUND!                ║
╚════════════════════════════════════════╝
[✓] VULNERABLE PHAR: Laravel_RCE1_phpcode.phar
[✓] Full Path: ./phar_gadgets/Laravel_RCE1_phpcode.phar
[✓] Method: file_get_contents
[✓] POC File: /tmp/poc created

[!] Stopping test - vulnerable gadget found!
```

### Features

- ✓ Fast execution
- ✓ Tests 4 common methods automatically
- ✓ Minimal output
- ✓ Stops when /tmp/poc is found
- ✓ Reports successful PHAR and method

---

## phar_checker_custom.php - Custom Deserialization

### When to Use

Use this when:
- You know the specific vulnerable code pattern in your target app
- You want to test a specific deserialization method
- You need to replicate exact application behavior

### Setup

1. Open `phar_checker_custom.php` in an editor
2. Find the `customDeserialize()` function
3. Add your custom deserialization code
4. Save and run

### Example: Custom Application Pattern

```php
function customDeserialize($pharPath) {
    // Example 1: Your app uses Phar class directly
    try {
        $phar = new Phar($pharPath);
        $metadata = $phar->getMetadata();
    } catch (Exception $e) {}
    
    // Example 2: Your app includes PHAR files
    @include("phar://$pharPath/config.php");
    
    // Example 3: Your app processes uploaded files
    $content = file_get_contents($pharPath);
    $data = unserialize($content);
    
    // Example 4: Your app has specific wrapper usage
    $config = parse_ini_file("phar://$pharPath/settings.ini");
}
```

### Usage

```bash
# After customizing the function
php phar_checker_custom.php ./phar_gadgets
```

### Output

```
╔════════════════════════════════════════╗
║     PHAR Checker - Custom Version     ║
╚════════════════════════════════════════╝

[*] Directory: ./phar_gadgets
[*] POC File: /tmp/poc
[*] Using custom deserialization function

[*] Scanning for PHAR files...
[+] Found 284 PHAR files

[1/284] Testing: Symfony_RCE1_cmd.phar
  [✗] POC not created

[2/284] Testing: Custom_Gadget.phar

╔════════════════════════════════════════╗
║       VULNERABLE GADGET FOUND!         ║
╚════════════════════════════════════════╝
[✓] SUCCESS!
[✓] PHAR File: Custom_Gadget.phar
[✓] Full Path: ./phar_gadgets/Custom_Gadget.phar
[✓] POC Created: /tmp/poc
[✓] Time: 3.45 seconds
[✓] Tested: 2/284 PHARs

[*] Result saved to: phar_success_20260217_153500.txt
[!] Stopping test - vulnerable gadget found!
```

### Features

- ✓ Fully customizable deserialization code
- ✓ Matches your specific application pattern
- ✓ Saves results to timestamped file
- ✓ Exception and error handling
- ✓ Stops on first success

---

## Quick Comparison

| Feature | Auto | Simple | Custom |
|---------|------|--------|--------|
| **Speed** | Medium | Fast | Medium |
| **Methods** | 5 (selectable) | 4 (fixed) | Custom |
| **Logging** | Detailed | None | Basic |
| **Customization** | Method selection | None | Full code |
| **Best For** | Complete testing | Quick scans | Specific apps |
| **Output Detail** | High | Low | Medium |

---

## Common Workflows

### Workflow 1: Initial Scan

```bash
# Quick scan to see if any gadgets work
php phar_checker_simple.php ./phar_gadgets
```

### Workflow 2: Comprehensive Test

```bash
# Test with all methods for thorough coverage
php phar_checker_auto.php ./phar_gadgets all
```

### Workflow 3: Targeted Testing

```bash
# If you know file_exists is the vulnerable pattern
php phar_checker_auto.php ./phar_gadgets file_exists
```

### Workflow 4: Application-Specific

```bash
# 1. Customize phar_checker_custom.php with your app's code
# 2. Run the test
php phar_checker_custom.php ./phar_gadgets
```

---

## Integration Examples

### Example 1: Testing Uploaded Files

If your application has an upload feature:

```php
// In phar_checker_custom.php customDeserialize()
function customDeserialize($pharPath) {
    // Simulate your upload handler
    $_FILES['upload']['tmp_name'] = $pharPath;
    
    // Call your vulnerable function
    include('/path/to/your/upload_handler.php');
    processUpload($_FILES['upload']);
}
```

### Example 2: Testing API Endpoints

```php
function customDeserialize($pharPath) {
    // Read PHAR
    $pharData = file_get_contents($pharPath);
    
    // Simulate API call
    $ch = curl_init('http://localhost/api/process');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['data' => $pharData]);
    curl_exec($ch);
    curl_close($ch);
}
```

### Example 3: Testing Specific Classes

```php
function customDeserialize($pharPath) {
    // If your app uses specific classes
    require_once '/path/to/VulnerableClass.php';
    
    // Trigger the vulnerable pattern
    $obj = new VulnerableClass();
    $obj->loadFromPhar($pharPath);
}
```

---

## Exit Codes

All PHP checkers use standard exit codes:

- `0` - Success (vulnerable gadget found)
- `1` - Failure (no vulnerable gadget found or error)

This allows for scripting:

```bash
#!/bin/bash
if php phar_checker_simple.php ./phar_gadgets; then
    echo "Vulnerability found!"
    # Trigger alert, save results, etc.
else
    echo "No vulnerability found"
fi
```

---

## Troubleshooting

### /tmp/poc Not Created

**Problem**: PHARs are tested but /tmp/poc is never created

**Solutions**:
1. Check PHP has write permissions to /tmp/
   ```bash
   php -r "touch('/tmp/test'); echo 'OK';"
   ```

2. Verify deserialization is actually happening
   - Enable error reporting in PHP
   - Check PHP error logs
   - Add debug output to customDeserialize()

3. Try different methods
   ```bash
   php phar_checker_auto.php ./phar_gadgets file_get
   php phar_checker_auto.php ./phar_gadgets file_exists
   ```

### Script Stops Immediately

**Problem**: Script exits without testing

**Solutions**:
1. Check directory path is correct
   ```bash
   ls -la ./phar_gadgets/
   ```

2. Verify .phar files exist
   ```bash
   find ./phar_gadgets -name "*.phar" | wc -l
   ```

3. Check file permissions
   ```bash
   chmod 644 ./phar_gadgets/*.phar
   ```

### PHP Errors/Warnings

**Problem**: PHP throws errors during testing

**Solutions**:
1. Suppress errors (already done with @)
2. Update PHP version
3. Check for missing extensions

### Wrong PHAR Reported

**Problem**: Script reports success but you can't reproduce

**Solutions**:
1. Note the exact method used
2. Check the full path in output
3. Verify /tmp/poc exists when script reports success
4. Test that specific PHAR manually

---

## Performance Tips

1. **Pre-filter PHARs**: Test smaller subsets first
   ```bash
   php phar_checker_simple.php ./phar_gadgets/Symfony*
   ```

2. **Use Simple checker first**: Quick initial scan
   ```bash
   php phar_checker_simple.php ./phar_gadgets
   ```

3. **Then use Auto for details**: Comprehensive testing
   ```bash
   php phar_checker_auto.php ./phar_gadgets
   ```

4. **Parallel testing**: Split directory and run multiple instances
   ```bash
   php phar_checker_simple.php ./phar_gadgets/A* &
   php phar_checker_simple.php ./phar_gadgets/B* &
   ```

---

## Security Notes

⚠️ **These tools are for authorized testing only**

- Only test systems you own or have permission to test
- PHARs contain exploit code - handle carefully
- Don't run on production systems
- Clean up /tmp/poc after testing
- Review generated PHARs before use

---

## Tips for Success

1. **Start Simple**: Use `phar_checker_simple.php` for initial testing
2. **Be Patient**: Some gadgets may take time to trigger
3. **Check Permissions**: Ensure PHP can write to /tmp/
4. **Review Logs**: Check PHP error logs for clues
5. **Test Incrementally**: Test small batches if you have many PHARs
6. **Clean Between Tests**: Remove /tmp/poc between manual tests
7. **Document Success**: Save the working PHAR for later use

---

**Created by**: 0xbugatti  
**Purpose**: PHAR deserialization vulnerability assessment  
**Date**: February 2026
