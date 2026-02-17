# PHAR Gadget Testing - Quick Reference

## 📋 Complete Toolkit Overview

### Phase 1: Generate PHARs (Local Machine)
```bash
./setup.sh                  # One-time setup
./phar_generator.sh         # Generate all gadgets
# → Creates: phar_gadgets_YYYYMMDD_HHMMSS.zip
```

### Phase 2: Test PHARs (Debug/Target Machine)

## 🎯 Which Tool Should I Use?

```
Need quick scan?
├─ YES → phar_checker_simple.php ⚡
└─ NO
   └─ Need to customize deserialization code?
      ├─ YES → phar_checker_custom.php 🛠️
      └─ NO
         └─ Want detailed logging?
            ├─ YES → phar_checker_auto.php 📊
            └─ NO
               └─ Prefer Bash/Python?
                  ├─ Bash → phar_checker.sh 🐚
                  └─ Python → phar_checker.py 🐍
```

---

## 🚀 Quick Commands

### PHP Checkers (Recommended)

```bash
# 1. FASTEST - Simple & Quick
php phar_checker_simple.php ./phar_gadgets

# 2. DETAILED - Full logging & method selection
php phar_checker_auto.php ./phar_gadgets all
php phar_checker_auto.php ./phar_gadgets file_get

# 3. CUSTOM - Your own deserialization code
# Edit customDeserialize() first, then:
php phar_checker_custom.php ./phar_gadgets
```

### Bash/Python Checkers

```bash
# Bash version
./phar_checker.sh ./phar_gadgets "php phar_test_simple.php"

# Python version  
python3 phar_checker.py ./phar_gadgets "php phar_test_simple.php $PHAR"
```

---

## 📊 Tool Comparison

| Tool | Language | Speed | Output | Logging | Customization |
|------|----------|-------|--------|---------|---------------|
| **phar_checker_simple.php** | PHP | ⚡⚡⚡ Fast | Minimal | None | None |
| **phar_checker_auto.php** | PHP | ⚡⚡ Medium | Detailed | Full | Method selection |
| **phar_checker_custom.php** | PHP | ⚡⚡ Medium | Medium | Basic | Full code control |
| **phar_checker.sh** | Bash | ⚡ Slow | Detailed | Full | External PHP script |
| **phar_checker.py** | Python | ⚡⚡ Medium | Detailed | Full | External PHP script |

---

## 🎓 Usage Examples

### Example 1: Quick Initial Test
```bash
# See if ANY gadget works (30 seconds)
php phar_checker_simple.php ./phar_gadgets
```

### Example 2: Comprehensive Test
```bash
# Test all methods, detailed logs (5-10 minutes)
php phar_checker_auto.php ./phar_gadgets all
```

### Example 3: Specific Method Only
```bash
# You know file_exists is the vulnerable pattern
php phar_checker_auto.php ./phar_gadgets file_exists
```

### Example 4: Custom Application Pattern
```bash
# Edit phar_checker_custom.php to match your app
php phar_checker_custom.php ./phar_gadgets
```

### Example 5: Using Test Harnesses
```bash
# With provided test harnesses
./phar_checker.sh ./phar_gadgets "php phar_test_simple.php"
./phar_checker.sh ./phar_gadgets "php phar_test_advanced.php"
```

### Example 6: Custom PHP Application
```bash
# Test with your actual vulnerable app
./phar_checker.sh ./phar_gadgets "php /var/www/myapp/upload.php"
```

---

## 🔍 What Each File Does

### Generator (Local)
- **setup.sh** - Install phpggc, set permissions
- **phar_generator.sh** - Generate all PHAR gadgets

### PHP Checkers (Recommended)
- **phar_checker_simple.php** ⭐ - Quick scan, stops at first success
- **phar_checker_auto.php** - Full-featured with all methods
- **phar_checker_custom.php** - Define your own deserialization

### Shell Checkers
- **phar_checker.sh** - Bash version with external PHP
- **phar_checker.py** - Python version with external PHP

### Test Harnesses  
- **phar_test_simple.php** - Basic PHAR deserialization
- **phar_test_advanced.php** - 5 different methods

### Documentation
- **README.md** - Complete documentation
- **PHP_USAGE_GUIDE.md** - PHP checker detailed guide
- **QUICK_REFERENCE.md** - This file

---

## ⚡ Recommended Workflow

### For Most Users (Fastest Path to Success)
```bash
# Step 1: Generate (local machine)
./setup.sh
./phar_generator.sh

# Step 2: Transfer
scp phar_gadgets_*.zip user@target:/tmp/

# Step 3: Test (target machine)
unzip phar_gadgets_*.zip
php phar_checker_simple.php ./phar_gadgets_*

# If found, done! If not:
php phar_checker_auto.php ./phar_gadgets_* all
```

### For Advanced Users (Custom Testing)
```bash
# 1. Edit phar_checker_custom.php
# 2. Add your app's vulnerable code pattern
# 3. Run it
php phar_checker_custom.php ./phar_gadgets
```

---

## 🎯 Success Indicators

### All Tools Look For:
- **File**: `/tmp/poc`
- **What**: File creation after PHAR processing
- **When**: Immediately stops when detected
- **Output**: Reports the vulnerable PHAR file

### What Success Looks Like:
```
╔════════════════════════════════════════╗
║       VULNERABLE GADGET FOUND!         ║
╚════════════════════════════════════════╝
[✓] PHAR File: Symfony_RCE1_cmd.phar
[✓] Full Path: ./phar_gadgets/Symfony_RCE1_cmd.phar
[✓] POC Created: /tmp/poc
```

---

## 🔧 Troubleshooting Quick Fixes

### Problem: No PHARs found
```bash
# Check directory
ls -la ./phar_gadgets/*.phar
# Verify path
realpath ./phar_gadgets
```

### Problem: /tmp/poc never created
```bash
# Check permissions
php -r "touch('/tmp/poc'); echo 'OK';"
# Try different method
php phar_checker_auto.php ./phar_gadgets file_exists
```

### Problem: Too slow
```bash
# Use simple checker first
php phar_checker_simple.php ./phar_gadgets
# Or test subset
php phar_checker_simple.php ./phar_gadgets/Symfony*
```

### Problem: Can't customize
```bash
# Use custom checker
cp phar_checker_custom.php my_custom.php
# Edit my_custom.php customDeserialize() function
php my_custom.php ./phar_gadgets
```

---

## 📖 Where to Learn More

- **Complete Guide**: `README.md`
- **PHP Details**: `PHP_USAGE_GUIDE.md`
- **Examples**: Check each script's header comments

---

## 🎬 One-Line Quick Start

```bash
# Generate (local)
./setup.sh && ./phar_generator.sh

# Test (target) - Choose one:
php phar_checker_simple.php ./phar_gadgets              # Fastest
php phar_checker_auto.php ./phar_gadgets all            # Most thorough
php phar_checker_custom.php ./phar_gadgets              # Most flexible
```

---

## ⚠️ Important Notes

1. **PHP checkers are RECOMMENDED** - Faster and more reliable
2. **Simple checker first** - Quick initial scan
3. **Auto checker next** - If simple finds nothing
4. **Custom checker last** - For specific application patterns
5. **All stop automatically** - When /tmp/poc is created
6. **Authorized testing only** - Only on systems you own/have permission

---

**Quick Help:**
- Not sure which to use? → Start with `phar_checker_simple.php`
- Want detailed logs? → Use `phar_checker_auto.php`
- Need custom code? → Use `phar_checker_custom.php`
- PHARs not working? → Check `/tmp/` permissions
- Too many PHARs? → Test smaller batches

---

**Created by**: 0xbugatti  
**Version**: 2.0  
**Date**: February 2026
