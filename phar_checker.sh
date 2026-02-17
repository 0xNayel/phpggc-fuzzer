#!/bin/bash

# PHAR Gadget Checker
# Tests PHAR files for successful exploitation by checking /tmp/poc creation

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

POC_FILE="/tmp/poc"
RESULTS_FILE="phar_test_results_$(date +%Y%m%d_%H%M%S).txt"

# Usage check
if [ $# -lt 2 ]; then
    echo "Usage: $0 <phar_directory> <php_deserialization_command>"
    echo ""
    echo "Examples:"
    echo "  $0 ./phar_gadgets 'php -r \"unserialize(file_get_contents(\\\$argv[1]));\"'"
    echo "  $0 ./phar_gadgets 'php test_app.php'"
    echo ""
    echo "The PHP command will receive the PHAR file path as an argument."
    exit 1
fi

PHAR_DIR="$1"
PHP_CMD="$2"

if [ ! -d "$PHAR_DIR" ]; then
    echo -e "${RED}[-] Directory not found: $PHAR_DIR${NC}"
    exit 1
fi

echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     PHAR Gadget Chain Checker         ║${NC}"
echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo ""
echo -e "${BLUE}[*] PHAR Directory: $PHAR_DIR${NC}"
echo -e "${BLUE}[*] Results File: $RESULTS_FILE${NC}"
echo -e "${BLUE}[*] Indicator File: $POC_FILE${NC}"
echo ""

# Initialize results file
cat > "$RESULTS_FILE" << EOF
PHAR Gadget Chain Test Results
===============================
Test Date: $(date)
PHAR Directory: $PHAR_DIR
PHP Command: $PHP_CMD

EOF

# Find all PHAR files
PHAR_FILES=$(find "$PHAR_DIR" -type f -name "*.phar" | sort)
TOTAL=$(echo "$PHAR_FILES" | wc -l)

if [ $TOTAL -eq 0 ]; then
    echo -e "${RED}[-] No PHAR files found in $PHAR_DIR${NC}"
    exit 1
fi

echo -e "${GREEN}[+] Found $TOTAL PHAR files to test${NC}"
echo ""

CURRENT=0
SUCCESS=0
FAILED=0
SUCCESSFUL_PHARS=()

# Test each PHAR
for PHAR in $PHAR_FILES; do
    CURRENT=$((CURRENT + 1))
    PHAR_NAME=$(basename "$PHAR")
    
    echo -e "${YELLOW}[$CURRENT/$TOTAL] Testing: $PHAR_NAME${NC}"
    
    # Clean up any existing POC file
    [ -f "$POC_FILE" ] && rm -f "$POC_FILE"
    
    # Build the command with PHAR path
    TEST_CMD=$(echo "$PHP_CMD" | sed "s|\$argv\[1\]|$PHAR|g")
    if [[ ! "$TEST_CMD" =~ "$PHAR" ]]; then
        TEST_CMD="$PHP_CMD $PHAR"
    fi
    
    # Execute PHP deserialization
    echo -e "  ${BLUE}[*] Executing: $TEST_CMD${NC}"
    
    # Run with timeout to prevent hanging
    if timeout 10 bash -c "$TEST_CMD" 2>/dev/null; then
        EXEC_STATUS="success"
    else
        EXEC_STATUS="failed/timeout"
    fi
    
    # Small delay to ensure file creation
    sleep 0.5
    
    # Check if POC file was created
    if [ -f "$POC_FILE" ]; then
        SUCCESS=$((SUCCESS + 1))
        SUCCESSFUL_PHARS+=("$PHAR_NAME")
        
        echo -e "${GREEN}  [✓] SUCCESS! /tmp/poc was created${NC}"
        echo -e "${GREEN}  [!] VULNERABLE GADGET FOUND!${NC}"
        echo ""
        
        # Log to results file
        cat >> "$RESULTS_FILE" << EOF
[SUCCESS] $PHAR_NAME
  Execution Status: $EXEC_STATUS
  POC File Created: YES
  Timestamp: $(date)
  
EOF
        
        # Prompt user if they want to continue
        echo -e "${YELLOW}  [?] Continue testing other PHARs? (y/n) ${NC}"
        read -t 10 -n 1 CONTINUE || CONTINUE="y"
        echo ""
        
        if [[ ! "$CONTINUE" =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}[*] Stopping test as requested${NC}"
            break
        fi
        
        # Clean up POC file for next test
        rm -f "$POC_FILE"
    else
        FAILED=$((FAILED + 1))
        echo -e "${RED}  [✗] Failed - /tmp/poc not created${NC}"
        
        # Log to results file
        cat >> "$RESULTS_FILE" << EOF
[FAILED] $PHAR_NAME
  Execution Status: $EXEC_STATUS
  POC File Created: NO
  
EOF
    fi
    
    echo ""
done

# Final summary
cat >> "$RESULTS_FILE" << EOF

Summary
=======
Total PHARs Tested: $TOTAL
Successful: $SUCCESS
Failed: $FAILED

EOF

if [ $SUCCESS -gt 0 ]; then
    echo -e "${GREEN}Successful Gadgets:${NC}" >> "$RESULTS_FILE"
    for PHAR in "${SUCCESSFUL_PHARS[@]}"; do
        echo "  - $PHAR" >> "$RESULTS_FILE"
    done
fi

# Display final summary
echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║          Test Summary                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
echo -e "Total PHARs Tested: $TOTAL"
echo -e "${GREEN}Successful: $SUCCESS${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $SUCCESS -gt 0 ]; then
    echo -e "${GREEN}[+] Vulnerable gadgets found:${NC}"
    for PHAR in "${SUCCESSFUL_PHARS[@]}"; do
        echo -e "  ${GREEN}✓ $PHAR${NC}"
    done
    echo ""
fi

echo -e "${BLUE}[*] Detailed results saved to: $RESULTS_FILE${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
