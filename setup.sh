#!/bin/bash

# PHAR Gadget Automation Toolkit - Setup Script
# Prepares the environment for PHAR gadget testing

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  PHAR Gadget Toolkit - Setup          ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
echo ""

# Check for phpggc
echo -e "${YELLOW}[*] Checking for phpggc...${NC}"
if [ ! -f "./phpggc" ]; then
    echo -e "${RED}[-] phpggc not found in current directory${NC}"
    echo -e "${YELLOW}[*] Would you like to clone phpggc? (y/n)${NC}"
    read -n 1 CLONE
    echo ""
    
    if [[ "$CLONE" =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}[*] Cloning phpggc...${NC}"
        git clone https://github.com/ambionics/phpggc.git
        mv phpggc/phpggc ./
        chmod +x phpggc
        echo -e "${GREEN}[+] phpggc installed${NC}"
    else
        echo -e "${RED}[-] Please install phpggc manually${NC}"
        echo -e "${YELLOW}    git clone https://github.com/ambionics/phpggc.git${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}[+] phpggc found${NC}"
fi

# Make scripts executable
echo -e "${YELLOW}[*] Setting execute permissions...${NC}"
chmod +x phar_generator.sh 2>/dev/null || true
chmod +x phar_checker.sh 2>/dev/null || true
chmod +x phar_checker.py 2>/dev/null || true
echo -e "${GREEN}[+] Permissions set${NC}"

# Check dependencies
echo ""
echo -e "${YELLOW}[*] Checking dependencies...${NC}"

# Check zip
if command -v zip &> /dev/null; then
    echo -e "${GREEN}[+] zip found${NC}"
else
    echo -e "${RED}[-] zip not found (required for compression)${NC}"
    echo -e "${YELLOW}    Install: apt-get install zip${NC}"
fi

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    echo -e "${GREEN}[+] PHP found: $PHP_VERSION${NC}"
else
    echo -e "${RED}[-] PHP not found (required for testing)${NC}"
    echo -e "${YELLOW}    Install: apt-get install php-cli${NC}"
fi

# Check Python
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version)
    echo -e "${GREEN}[+] Python found: $PYTHON_VERSION${NC}"
else
    echo -e "${YELLOW}[-] Python3 not found (optional, for Python checker)${NC}"
fi

# Test phpggc
echo ""
echo -e "${YELLOW}[*] Testing phpggc...${NC}"
if ./phpggc -l &> /dev/null; then
    GADGET_COUNT=$(./phpggc -l | grep -c "^[A-Za-z]" || true)
    echo -e "${GREEN}[+] phpggc working - $GADGET_COUNT gadget chains available${NC}"
else
    echo -e "${RED}[-] phpggc test failed${NC}"
    exit 1
fi

# Create test directory structure
echo ""
echo -e "${YELLOW}[*] Creating directory structure...${NC}"
mkdir -p test_output
echo -e "${GREEN}[+] Directories created${NC}"

# Summary
echo ""
echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║          Setup Complete                ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
echo ""
echo -e "Available scripts:"
echo -e "  ${GREEN}1. phar_generator.sh${NC}  - Generate PHAR gadgets"
echo -e "  ${GREEN}2. phar_checker.sh${NC}    - Test PHARs (Bash)"
echo -e "  ${GREEN}3. phar_checker.py${NC}    - Test PHARs (Python)"
echo -e "  ${GREEN}4. phar_test_simple.php${NC} - Simple PHP test harness"
echo -e "  ${GREEN}5. phar_test_advanced.php${NC} - Advanced PHP test harness"
echo ""
echo -e "Next steps:"
echo -e "  ${YELLOW}1.${NC} Generate PHARs: ${GREEN}./phar_generator.sh${NC}"
echo -e "  ${YELLOW}2.${NC} Transfer ZIP to target machine"
echo -e "  ${YELLOW}3.${NC} Run checker on target machine"
echo ""
echo -e "See ${GREEN}README.md${NC} for detailed usage instructions"
echo ""
