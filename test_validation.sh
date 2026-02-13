#!/bin/bash
# Test Validation Script for Payment Plugin Confirmation Pages
# This script validates that the implementation meets all requirements

echo "=========================================="
echo "Payment Plugin Confirmation Page Tests"
echo "=========================================="
echo ""

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

passed=0
failed=0

# Test 1: Check files exist
echo "Test 1: Checking required files exist..."
if [ -f "plugins/solidrespayment/qvik/asset/confirmation.php" ] && \
   [ -f "plugins/solidrespayment/revolut/asset/confirmation.php" ]; then
    echo -e "${GREEN}✓ PASSED${NC} - Both confirmation.php files exist"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Missing confirmation.php files"
    ((failed++))
fi
echo ""

# Test 2: Check buildAjaxUrl function exists in both files
echo "Test 2: Checking buildAjaxUrl function exists..."
qvik_ajax=$(grep -c "function buildAjaxUrl" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_ajax=$(grep -c "function buildAjaxUrl" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_ajax" -ge 1 ] && [ "$revolut_ajax" -ge 1 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - buildAjaxUrl function exists in both files"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - buildAjaxUrl function missing"
    ((failed++))
fi
echo ""

# Test 3: Check buildRedirectUrl function exists in both files
echo "Test 3: Checking buildRedirectUrl function exists..."
qvik_redirect=$(grep -c "function buildRedirectUrl" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_redirect=$(grep -c "function buildRedirectUrl" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_redirect" -ge 1 ] && [ "$revolut_redirect" -ge 1 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - buildRedirectUrl function exists in both files"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - buildRedirectUrl function missing"
    ((failed++))
fi
echo ""

# Test 4: Check path preservation logic
echo "Test 4: Checking path preservation logic..."
qvik_pathname=$(grep -c "window.location.pathname" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_pathname=$(grep -c "window.location.pathname" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_pathname" -ge 2 ] && [ "$revolut_pathname" -ge 2 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - Path preservation logic present"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Path preservation logic missing"
    ((failed++))
fi
echo ""

# Test 5: Check parameter preservation
echo "Test 5: Checking parameter preservation (Itemid, property_id, hub_id)..."
qvik_preserve=$(grep -c "preserveParams.*Itemid.*property_id.*hub_id" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_preserve=$(grep -c "preserveParams.*Itemid.*property_id.*hub_id" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_preserve" -ge 1 ] && [ "$revolut_preserve" -ge 1 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - Parameter preservation logic present"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Parameter preservation logic missing (found: qvik=$qvik_preserve, revolut=$revolut_preserve)"
    ((failed++))
fi
echo ""

# Test 6: Check for comprehensive comments
echo "Test 6: Checking for comprehensive code comments..."
qvik_comments=$(grep -c "WHY THIS IS NECESSARY" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_comments=$(grep -c "WHY THIS IS NECESSARY" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_comments" -ge 2 ] && [ "$revolut_comments" -ge 2 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - Comprehensive comments present"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Insufficient code comments (found: qvik=$qvik_comments, revolut=$revolut_comments)"
    ((failed++))
fi
echo ""

# Test 7: Check fetch() API usage
echo "Test 7: Checking fetch() API usage with proper configuration..."
qvik_fetch=$(grep -c "fetch(ajaxUrl" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_fetch=$(grep -c "fetch(ajaxUrl" plugins/solidrespayment/revolut/asset/confirmation.php)
qvik_creds=$(grep -c "credentials.*same-origin" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_creds=$(grep -c "credentials.*same-origin" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_fetch" -ge 1 ] && [ "$revolut_fetch" -ge 1 ] && \
   [ "$qvik_creds" -ge 1 ] && [ "$revolut_creds" -ge 1 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - fetch() API properly configured with credentials"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - fetch() API not properly configured"
    ((failed++))
fi
echo ""

# Test 8: Check error handling
echo "Test 8: Checking error handling with catch blocks..."
qvik_catch=$(grep -c "\.catch(function" plugins/solidrespayment/qvik/asset/confirmation.php)
revolut_catch=$(grep -c "\.catch(function" plugins/solidrespayment/revolut/asset/confirmation.php)

if [ "$qvik_catch" -ge 1 ] && [ "$revolut_catch" -ge 1 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - Error handling present"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Error handling missing"
    ((failed++))
fi
echo ""

# Test 9: Check documentation exists
echo "Test 9: Checking documentation files..."
if [ -f "IMPLEMENTATION_NOTES.md" ] && [ -f "plugins/solidrespayment/README.md" ]; then
    echo -e "${GREEN}✓ PASSED${NC} - Documentation files present"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Documentation missing"
    ((failed++))
fi
echo ""

# Test 10: Check no security anti-patterns
echo "Test 10: Checking for security anti-patterns..."
eval_count=0
function_count=0

if grep -q "eval(" plugins/solidrespayment/*/asset/confirmation.php 2>/dev/null; then
    eval_count=1
fi

if grep -q "new Function" plugins/solidrespayment/*/asset/confirmation.php 2>/dev/null; then
    function_count=1
fi

if [ "$eval_count" -eq 0 ] && [ "$function_count" -eq 0 ]; then
    echo -e "${GREEN}✓ PASSED${NC} - No dangerous eval() or Function() usage"
    ((passed++))
else
    echo -e "${RED}✗ FAILED${NC} - Security anti-patterns detected"
    ((failed++))
fi
echo ""

# Summary
echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo -e "Passed: ${GREEN}$passed${NC}"
echo -e "Failed: ${RED}$failed${NC}"
echo ""

if [ $failed -eq 0 ]; then
    echo -e "${GREEN}✓ ALL TESTS PASSED!${NC}"
    echo "The implementation meets all requirements."
    exit 0
else
    echo -e "${RED}✗ SOME TESTS FAILED${NC}"
    echo "Please review the failures above."
    exit 1
fi
