#!/bin/bash

###############################################################################
# BRODERO - Quick Check Script
# Verificare rapidă a sistemului de email
# Rulează: bash quick_check.sh
###############################################################################

echo "=================================================="
echo "🔍 BRODERO EMAIL SYSTEM - QUICK CHECK"
echo "=================================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0

###############################################################################
# Helper Functions
###############################################################################

check_pass() {
    echo -e "${GREEN}✅ PASS${NC} - $1"
    ((PASSED++))
}

check_fail() {
    echo -e "${RED}❌ FAIL${NC} - $1"
    ((FAILED++))
}

check_warn() {
    echo -e "${YELLOW}⚠️  WARN${NC} - $1"
}

###############################################################################
# CHECK 1: PHPMailer Installation
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Check 1: PHPMailer Installation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -d "vendor/phpmailer/phpmailer" ]; then
    check_pass "PHPMailer directory exists"
    
    # Check version
    if [ -f "vendor/phpmailer/phpmailer/src/PHPMailer.php" ]; then
        VERSION=$(grep -m1 "VERSION = " vendor/phpmailer/phpmailer/src/PHPMailer.php | sed "s/.*'\(.*\)'.*/\1/")
        echo "   Version: $VERSION"
    fi
else
    check_fail "PHPMailer not found! Run: composer update"
fi

echo ""

###############################################################################
# CHECK 2: Configuration Files
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⚙️  Check 2: Configuration Files"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check smtp_config.php exists
if [ -f "config/smtp_config.php" ]; then
    check_pass "smtp_config.php exists"
    
    # Check password placeholder
    if grep -q "PUNE_PAROLA_AICI" config/smtp_config.php; then
        check_fail "SMTP password is still placeholder! Edit config/smtp_config.php"
    else
        check_pass "SMTP password has been set"
    fi
    
    # Check SMTP settings
    SMTP_HOST=$(grep "SMTP_HOST" config/smtp_config.php | head -1 | sed "s/.*'\(.*\)'.*/\1/")
    SMTP_PORT=$(grep "SMTP_PORT" config/smtp_config.php | head -1 | sed "s/.*[^0-9]\([0-9]\+\).*/\1/")
    echo "   SMTP Host: $SMTP_HOST"
    echo "   SMTP Port: $SMTP_PORT"
    
else
    check_fail "config/smtp_config.php not found!"
fi

# Check process_contact.php
if [ -f "includes/forms/process_contact.php" ]; then
    check_pass "process_contact.php exists"
    
    # Check for PHPMailer usage
    if grep -q "use PHPMailer" includes/forms/process_contact.php; then
        check_pass "PHPMailer is being used (not mail())"
    else
        check_warn "PHPMailer usage not detected in process_contact.php"
    fi
else
    check_fail "includes/forms/process_contact.php not found!"
fi

echo ""

###############################################################################
# CHECK 3: Directory Permissions
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 Check 3: Directory Permissions"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check logs directory
if [ -d "logs" ]; then
    check_pass "logs/ directory exists"
    
    if [ -w "logs" ]; then
        check_pass "logs/ is writable"
    else
        check_fail "logs/ is NOT writable! Run: chmod 755 logs/"
    fi
    
    # Check log file
    if [ -f "logs/mail.log" ]; then
        LOG_SIZE=$(du -h logs/mail.log | cut -f1)
        echo "   mail.log size: $LOG_SIZE"
        
        # Count log entries
        LOG_COUNT=$(wc -l < logs/mail.log)
        echo "   Log entries: $LOG_COUNT lines"
    else
        check_warn "logs/mail.log doesn't exist yet (will be created automatically)"
    fi
else
    check_fail "logs/ directory not found! Run: mkdir -p logs && chmod 755 logs"
fi

echo ""

###############################################################################
# CHECK 4: PHP Extensions
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 Check 4: PHP Extensions"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check required extensions
REQUIRED_EXTS=("openssl" "mbstring" "mysqli")

for EXT in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -q "^$EXT$"; then
        check_pass "$EXT extension loaded"
    else
        check_fail "$EXT extension NOT loaded!"
    fi
done

# Show PHP version
PHP_VERSION=$(php -v | head -1 | awk '{print $2}')
echo "   PHP Version: $PHP_VERSION"

echo ""

###############################################################################
# CHECK 5: Security Files
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🛡️  Check 5: Security Configuration"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check .gitignore
if [ -f ".gitignore" ]; then
    check_pass ".gitignore exists"
    
    if grep -q "smtp_config.php" .gitignore; then
        check_pass "smtp_config.php is in .gitignore"
    else
        check_fail "smtp_config.php NOT in .gitignore! Add it to prevent password leaks!"
    fi
    
    if grep -q "logs/" .gitignore; then
        check_pass "logs/ is in .gitignore"
    else
        check_warn "Consider adding logs/ to .gitignore"
    fi
else
    check_warn ".gitignore not found"
fi

# Check for test files that should be deleted
if [ -f "test_email_smtp.php" ]; then
    check_fail "test_email_smtp.php still exists! DELETE IT: rm test_email_smtp.php"
else
    check_pass "No test files found in production"
fi

echo ""

###############################################################################
# CHECK 6: Contact Form Files
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 Check 6: Contact Form Implementation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "pages/contact.php" ]; then
    check_pass "pages/contact.php exists"
    
    # Check for CSRF protection
    if grep -q "csrf_token" pages/contact.php; then
        check_pass "CSRF protection implemented"
    else
        check_warn "CSRF token not detected in contact.php"
    fi
    
    # Check for honeypot
    if grep -q 'name="website"' pages/contact.php; then
        check_pass "Honeypot field implemented"
    else
        check_warn "Honeypot field not detected"
    fi
else
    check_fail "pages/contact.php not found!"
fi

echo ""

###############################################################################
# CHECK 7: Recent Logs Analysis
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Check 7: Recent Activity"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "logs/mail.log" ]; then
    # Count recent successes
    SUCCESS_COUNT=$(grep -c "\[SUCCESS\]" logs/mail.log 2>/dev/null || echo "0")
    ERROR_COUNT=$(grep -c "\[ERROR\]" logs/mail.log 2>/dev/null || echo "0")
    WARNING_COUNT=$(grep -c "\[WARNING\]" logs/mail.log 2>/dev/null || echo "0")
    
    echo "   Recent log statistics:"
    echo "   - Successes: $SUCCESS_COUNT"
    echo "   - Errors: $ERROR_COUNT"
    echo "   - Warnings: $WARNING_COUNT"
    
    if [ "$ERROR_COUNT" -gt 0 ]; then
        check_warn "$ERROR_COUNT errors found in logs. Check: tail logs/mail.log"
    else
        check_pass "No errors in logs"
    fi
    
    # Show last 3 log entries
    echo ""
    echo "   Last 3 log entries:"
    tail -n 3 logs/mail.log | sed 's/^/   | /'
    
else
    check_warn "No log file yet - system hasn't been tested"
fi

echo ""

###############################################################################
# FINAL SUMMARY
###############################################################################

echo "=================================================="
echo "📋 SUMMARY"
echo "=================================================="
echo ""
echo -e "${GREEN}✅ Passed: $PASSED${NC}"
echo -e "${RED}❌ Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL CHECKS PASSED! System is ready for production.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Test form at: https://brodero.online/pages/contact.php"
    echo "2. Monitor logs: tail -f logs/mail.log"
    echo "3. Check inbox: https://webmail.hostinger.com/"
else
    echo -e "${RED}⚠️  SOME CHECKS FAILED! Fix issues above before going live.${NC}"
    echo ""
    echo "Common fixes:"
    echo "1. Install PHPMailer: composer update"
    echo "2. Set SMTP password: nano config/smtp_config.php"
    echo "3. Create logs dir: mkdir -p logs && chmod 755 logs"
    echo "4. Delete test files: rm test_email_smtp.php"
fi

echo ""
echo "For detailed setup guide, see: DEPLOYMENT_STEPS.md"
echo "=================================================="
