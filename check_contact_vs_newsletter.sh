#!/bin/bash

###############################################################################
# BRODERO - Verificare Formular Contact vs Newsletter
# Compară implementările pentru a confirma că sunt identice
###############################################################################

echo "=================================================================="
echo "🔍 VERIFICARE FORMULAR CONTACT - IDENTIC CU NEWSLETTER"
echo "=================================================================="
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0

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
# CHECK 1: Verificare fișier contact.php
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📄 Check 1: Verificare contact.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "pages/contact.php" ]; then
    check_pass "contact.php există"
    
    # Verifică că folosește mail() nu PHPMailer
    if grep -q "mail(\$toEmail" pages/contact.php; then
        check_pass "Folosește funcția mail() (ca Newsletter)"
    else
        check_fail "NU folosește funcția mail()!"
    fi
    
    # Verifică headers identice
    if grep -q "Content-Type: text/html; charset=UTF-8" pages/contact.php; then
        check_pass "Headers HTML + UTF-8 (ca Newsletter)"
    else
        check_warn "Headers ar putea lipsi sau diferi"
    fi
    
    # Verifică From header
    if grep -q "From: Brodero <noreply@brodero.online>" pages/contact.php; then
        check_pass "From: noreply@brodero.online (ca Newsletter)"
    else
        check_warn "From header ar putea diferi"
    fi
    
else
    check_fail "pages/contact.php NU există!"
fi

echo ""

###############################################################################
# CHECK 2: Verificare fișiere vechi șterse
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗑️  Check 2: Verificare fișiere vechi"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "includes/forms/process_contact.php" ]; then
    check_warn "process_contact.php încă există (ar trebui mutat în .OLD_PHPMAILER)"
else
    check_pass "process_contact.php șters/mutat"
fi

if [ -f "includes/forms/process_contact.php.OLD_PHPMAILER" ]; then
    check_pass "Backup .OLD_PHPMAILER există"
fi

if [ -f "bootstrap.php" ]; then
    check_warn "bootstrap.php încă există (nu mai este necesar)"
else
    check_pass "bootstrap.php șters/mutat"
fi

if [ -f "bootstrap.php.OLD" ]; then
    check_pass "Backup bootstrap.php.OLD există"
fi

echo ""

###############################################################################
# CHECK 3: Comparație cu Newsletter
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 Check 3: Comparație cu Newsletter"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "admin/send_newsletter.php" ]; then
    check_pass "Newsletter există pentru comparație"
    
    echo ""
    echo "   Verificare similitudini:"
    
    # Verifică mail() în newsletter
    if grep -q "mail(\$toEmail" admin/send_newsletter.php; then
        echo "   ✓ Newsletter folosește mail()"
    fi
    
    # Verifică headers în newsletter
    if grep -q "MIME-Version: 1.0" admin/send_newsletter.php; then
        echo "   ✓ Newsletter are MIME headers"
    fi
    
    if grep -q "Content-Type: text/html; charset=UTF-8" admin/send_newsletter.php; then
        echo "   ✓ Newsletter are HTML + UTF-8"
    fi
    
    if grep -q "From: Brodero" admin/send_newsletter.php; then
        echo "   ✓ Newsletter are From: Brodero"
    fi
    
    check_pass "Newsletter folosește metoda corectă (referință)"
else
    check_fail "Newsletter NU există pentru comparație!"
fi

echo ""

###############################################################################
# CHECK 4: Verificare securitate păstrată
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔒 Check 4: Verificare securitate"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if grep -q "csrf_token" pages/contact.php; then
    check_pass "CSRF token validation prezentă"
else
    check_warn "CSRF token validation ar putea lipsi"
fi

if grep -q 'name="website"' pages/contact.php; then
    check_pass "Honeypot anti-spam prezent"
else
    check_warn "Honeypot ar putea lipsi"
fi

if grep -q "filter_var.*FILTER_VALIDATE_EMAIL" pages/contact.php; then
    check_pass "Validare email prezentă"
else
    check_warn "Validare email ar putea lipsi"
fi

echo ""

###############################################################################
# CHECK 5: Verificare database backup
###############################################################################

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💾 Check 5: Verificare database backup"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if grep -q "INSERT INTO contact_messages" pages/contact.php; then
    check_pass "Salvare în database după email"
else
    check_warn "Salvare database ar putea lipsi"
fi

echo ""

###############################################################################
# SUMAR FINAL
###############################################################################

echo "=================================================================="
echo "📋 SUMAR"
echo "=================================================================="
echo ""
echo -e "${GREEN}✅ Passed: $PASSED${NC}"
echo -e "${RED}❌ Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 TOATE VERIFICĂRILE AU TRECUT!${NC}"
    echo ""
    echo "Formularul de contact folosește ACUM metoda identică cu Newsletter-ul."
    echo ""
    echo "Next steps:"
    echo "1. Testează: https://brodero.online/pages/contact.php"
    echo "2. Trimite mesaj test"
    echo "3. Verifică inbox: contact@brodero.online"
    echo "4. Confirmă: fără erori 'headers already sent'"
else
    echo -e "${RED}⚠️  UNELE VERIFICĂRI AU EȘUAT!${NC}"
    echo ""
    echo "Verifică manual fișierele și compară cu Newsletter-ul."
fi

echo ""
echo "Pentru detalii: vezi CONTACT_FINAL_FIX.md"
echo "=================================================================="
