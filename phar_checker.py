#!/usr/bin/env python3

"""
PHAR Gadget Chain Checker
Tests PHAR files for successful exploitation by checking /tmp/poc creation
"""

import os
import sys
import subprocess
import time
from pathlib import Path
from datetime import datetime
from typing import List, Tuple

# ANSI colors
class Colors:
    RED = '\033[0;31m'
    GREEN = '\033[0;32m'
    YELLOW = '\033[1;33m'
    BLUE = '\033[0;34m'
    NC = '\033[0m'

POC_FILE = "/tmp/poc"

def print_header():
    print(f"{Colors.GREEN}╔════════════════════════════════════════╗{Colors.NC}")
    print(f"{Colors.GREEN}║     PHAR Gadget Chain Checker         ║{Colors.NC}")
    print(f"{Colors.GREEN}╚════════════════════════════════════════╝{Colors.NC}")
    print()

def find_phar_files(directory: str) -> List[Path]:
    """Find all PHAR files in directory"""
    phar_dir = Path(directory)
    if not phar_dir.exists():
        print(f"{Colors.RED}[-] Directory not found: {directory}{Colors.NC}")
        sys.exit(1)
    
    phar_files = sorted(phar_dir.glob("**/*.phar"))
    return phar_files

def cleanup_poc():
    """Remove POC file if it exists"""
    if os.path.exists(POC_FILE):
        try:
            os.remove(POC_FILE)
        except OSError:
            pass

def test_phar(phar_path: Path, php_command: str, timeout: int = 10) -> Tuple[bool, str]:
    """
    Test a PHAR file by executing PHP deserialization
    Returns (success, status_message)
    """
    cleanup_poc()
    
    # Build command with PHAR path
    if "$PHAR" in php_command:
        cmd = php_command.replace("$PHAR", str(phar_path))
    else:
        cmd = f"{php_command} {phar_path}"
    
    print(f"  {Colors.BLUE}[*] Executing: {cmd}{Colors.NC}")
    
    try:
        # Execute with timeout
        result = subprocess.run(
            cmd,
            shell=True,
            timeout=timeout,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        exec_status = "success" if result.returncode == 0 else f"error (code {result.returncode})"
    except subprocess.TimeoutExpired:
        exec_status = "timeout"
    except Exception as e:
        exec_status = f"exception: {str(e)}"
    
    # Wait a bit for file creation
    time.sleep(0.5)
    
    # Check if POC file was created
    poc_created = os.path.exists(POC_FILE)
    
    return poc_created, exec_status

def save_results(results_file: str, total: int, success: int, failed: int, 
                successful_phars: List[str], phar_dir: str, php_cmd: str):
    """Save test results to file"""
    with open(results_file, 'w') as f:
        f.write("PHAR Gadget Chain Test Results\n")
        f.write("=" * 50 + "\n")
        f.write(f"Test Date: {datetime.now()}\n")
        f.write(f"PHAR Directory: {phar_dir}\n")
        f.write(f"PHP Command: {php_cmd}\n\n")
        f.write(f"Summary\n")
        f.write("=" * 50 + "\n")
        f.write(f"Total PHARs Tested: {total}\n")
        f.write(f"Successful: {success}\n")
        f.write(f"Failed: {failed}\n\n")
        
        if successful_phars:
            f.write("Successful Gadgets:\n")
            for phar in successful_phars:
                f.write(f"  - {phar}\n")

def main():
    if len(sys.argv) < 3:
        print("Usage: {} <phar_directory> <php_command>".format(sys.argv[0]))
        print()
        print("Examples:")
        print('  {} ./phar_gadgets "php -r \\"unserialize(file_get_contents(\\\$argv[1]));\\"" '.format(sys.argv[0]))
        print('  {} ./phar_gadgets "php test_app.php $PHAR"'.format(sys.argv[0]))
        print('  {} ./phar_gadgets "php vulnerable_app.php"'.format(sys.argv[0]))
        print()
        print("Note: Use $PHAR placeholder for PHAR file path in command")
        sys.exit(1)
    
    phar_dir = sys.argv[1]
    php_cmd = sys.argv[2]
    
    print_header()
    
    results_file = f"phar_test_results_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
    
    print(f"{Colors.BLUE}[*] PHAR Directory: {phar_dir}{Colors.NC}")
    print(f"{Colors.BLUE}[*] Results File: {results_file}{Colors.NC}")
    print(f"{Colors.BLUE}[*] Indicator File: {POC_FILE}{Colors.NC}")
    print()
    
    # Find all PHAR files
    phar_files = find_phar_files(phar_dir)
    total = len(phar_files)
    
    if total == 0:
        print(f"{Colors.RED}[-] No PHAR files found in {phar_dir}{Colors.NC}")
        sys.exit(1)
    
    print(f"{Colors.GREEN}[+] Found {total} PHAR files to test{Colors.NC}")
    print()
    
    success = 0
    failed = 0
    successful_phars = []
    
    # Test each PHAR
    for i, phar_path in enumerate(phar_files, 1):
        phar_name = phar_path.name
        
        print(f"{Colors.YELLOW}[{i}/{total}] Testing: {phar_name}{Colors.NC}")
        
        poc_created, exec_status = test_phar(phar_path, php_cmd)
        
        if poc_created:
            success += 1
            successful_phars.append(phar_name)
            
            print(f"{Colors.GREEN}  [✓] SUCCESS! /tmp/poc was created{Colors.NC}")
            print(f"{Colors.GREEN}  [!] VULNERABLE GADGET FOUND!{Colors.NC}")
            print(f"  Execution Status: {exec_status}")
            print()
            
            # Ask if user wants to continue
            try:
                response = input(f"{Colors.YELLOW}  [?] Continue testing other PHARs? (y/n): {Colors.NC}")
                if response.lower() != 'y':
                    print(f"{Colors.YELLOW}[*] Stopping test as requested{Colors.NC}")
                    break
            except KeyboardInterrupt:
                print(f"\n{Colors.YELLOW}[*] Test interrupted by user{Colors.NC}")
                break
            
            cleanup_poc()
        else:
            failed += 1
            print(f"{Colors.RED}  [✗] Failed - /tmp/poc not created{Colors.NC}")
            print(f"  Execution Status: {exec_status}")
        
        print()
    
    # Save results
    save_results(results_file, total, success, failed, successful_phars, phar_dir, php_cmd)
    
    # Display summary
    print(f"{Colors.GREEN}╔════════════════════════════════════════╗{Colors.NC}")
    print(f"{Colors.GREEN}║          Test Summary                  ║{Colors.NC}")
    print(f"{Colors.GREEN}╚════════════════════════════════════════╝{Colors.NC}")
    print(f"Total PHARs Tested: {total}")
    print(f"{Colors.GREEN}Successful: {success}{Colors.NC}")
    print(f"{Colors.RED}Failed: {failed}{Colors.NC}")
    print()
    
    if successful_phars:
        print(f"{Colors.GREEN}[+] Vulnerable gadgets found:{Colors.NC}")
        for phar in successful_phars:
            print(f"  {Colors.GREEN}✓ {phar}{Colors.NC}")
        print()
    
    print(f"{Colors.BLUE}[*] Detailed results saved to: {results_file}{Colors.NC}")
    print(f"{Colors.GREEN}╚════════════════════════════════════════╝{Colors.NC}")

if __name__ == "__main__":
    main()
