#!/bin/bash
# Solidres Router 404 Diagnostic Script
# This script checks for common issues that cause 404 errors even after applying the router patch

echo "========================================"
echo "Solidres Router 404 Diagnostic Tool"
echo "========================================"
echo ""

ERRORS=0
WARNINGS=0

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check 1: Router file location
echo "1. Checking router.php location..."
if [ -f "components/com_solidres/router.php" ]; then
    echo -e "${GREEN}✓${NC} Router file found at correct location"
else
    echo -e "${RED}✗${NC} Router file NOT found at components/com_solidres/router.php"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 2: Router file size
echo "2. Checking router.php file size..."
if [ -f "components/com_solidres/router.php" ]; then
    LINES=$(wc -l < components/com_solidres/router.php)
    if [ $LINES -gt 200 ]; then
        echo -e "${GREEN}✓${NC} Router file has $LINES lines (expected ~278)"
    else
        echo -e "${YELLOW}⚠${NC} Router file has only $LINES lines (expected ~278)"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}✗${NC} Cannot check - file not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 3: Critical methods
echo "3. Checking for critical methods..."
if [ -f "components/com_solidres/router.php" ]; then
    # Check for findItemIdForContext
    if grep -q "function findItemIdForContext" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} findItemIdForContext method found"
    else
        echo -e "${RED}✗${NC} findItemIdForContext method NOT found"
        ERRORS=$((ERRORS + 1))
    fi
    
    # Check for extractPathContext
    if grep -q "function extractPathContext" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} extractPathContext method found"
    else
        echo -e "${RED}✗${NC} extractPathContext method NOT found"
        ERRORS=$((ERRORS + 1))
    fi
    
    # Check for SolidresBuildRoute
    if grep -q "function SolidresBuildRoute" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} SolidresBuildRoute function found"
    else
        echo -e "${RED}✗${NC} SolidresBuildRoute function NOT found"
        ERRORS=$((ERRORS + 1))
    fi
    
    # Check for SolidresParseRoute
    if grep -q "function SolidresParseRoute" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} SolidresParseRoute function found"
    else
        echo -e "${RED}✗${NC} SolidresParseRoute function NOT found"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${RED}✗${NC} Cannot check - router file not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 4: Parse method implementation
echo "4. Checking parse() method implementation..."
if [ -f "components/com_solidres/router.php" ]; then
    # Check for task-based routing logic
    if grep -q "strpos(\$task, '.')" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} Task-based routing check found"
    else
        echo -e "${RED}✗${NC} Task-based routing check NOT found"
        ERRORS=$((ERRORS + 1))
    fi
    
    # Check for format check
    if grep -q "format === 'json'" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} JSON format check found"
    else
        echo -e "${YELLOW}⚠${NC} JSON format check NOT found"
        WARNINGS=$((WARNINGS + 1))
    fi
    
    # Check for findItemIdForContext call
    if grep -q "findItemIdForContext(" components/com_solidres/router.php; then
        echo -e "${GREEN}✓${NC} findItemIdForContext call found"
    else
        echo -e "${RED}✗${NC} findItemIdForContext call NOT found"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${RED}✗${NC} Cannot check - router file not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 5: Cache directories
echo "5. Checking cache status..."
if [ -d "cache" ]; then
    CACHE_FILES=$(find cache -type f 2>/dev/null | wc -l)
    if [ $CACHE_FILES -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Cache directory contains $CACHE_FILES files - consider clearing"
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "${GREEN}✓${NC} Cache directory is empty"
    fi
else
    echo -e "${YELLOW}⚠${NC} Cache directory not found"
fi

if [ -d "administrator/cache" ]; then
    ADMIN_CACHE_FILES=$(find administrator/cache -type f 2>/dev/null | wc -l)
    if [ $ADMIN_CACHE_FILES -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Admin cache contains $ADMIN_CACHE_FILES files - consider clearing"
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "${GREEN}✓${NC} Admin cache is empty"
    fi
else
    echo -e "${YELLOW}⚠${NC} Admin cache directory not found"
fi
echo ""

# Check 6: Controller file
echo "6. Checking controller file..."
if [ -f "components/com_solidres/controllers/reservationasset.php" ]; then
    echo -e "${GREEN}✓${NC} ReservationAsset controller found"
    
    # Check for updatePaymentMethod
    if grep -q "function updatePaymentMethod" components/com_solidres/controllers/reservationasset.php; then
        echo -e "${GREEN}✓${NC} updatePaymentMethod method found in controller"
    else
        echo -e "${YELLOW}⚠${NC} updatePaymentMethod method NOT found in controller"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${YELLOW}⚠${NC} Controller file not found (might be normal if not yet created)"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Check 7: Configuration
echo "7. Checking Joomla configuration..."
if [ -f "configuration.php" ]; then
    echo -e "${GREEN}✓${NC} configuration.php found"
    
    # Check debug mode
    if grep -q "public \$debug = '1'" configuration.php || grep -q 'public $debug = 1' configuration.php; then
        echo -e "${GREEN}✓${NC} Debug mode is enabled"
    else
        echo -e "${YELLOW}⚠${NC} Debug mode is disabled - enable for troubleshooting"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}✗${NC} configuration.php not found"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 8: Logs directory
echo "8. Checking logs..."
if [ -d "logs" ]; then
    echo -e "${GREEN}✓${NC} Logs directory exists"
    
    # Check for routing logs
    if [ -f "logs/com_solidres.routing.php" ]; then
        LOG_LINES=$(wc -l < logs/com_solidres.routing.php)
        echo -e "${GREEN}✓${NC} Routing log exists ($LOG_LINES lines)"
        
        # Show last few entries
        echo "   Last 3 routing log entries:"
        tail -3 logs/com_solidres.routing.php | sed 's/^/   /'
    else
        echo -e "${YELLOW}⚠${NC} No routing log found - logging may not be configured"
    fi
    
    # Check for error logs
    if [ -f "logs/error.php" ]; then
        ERROR_SIZE=$(wc -l < logs/error.php)
        if [ $ERROR_SIZE -gt 10 ]; then
            echo -e "${YELLOW}⚠${NC} Error log has $ERROR_SIZE lines - check for errors"
            echo "   Last 3 errors:"
            tail -3 logs/error.php | sed 's/^/   /'
        else
            echo -e "${GREEN}✓${NC} Error log is small ($ERROR_SIZE lines)"
        fi
    fi
else
    echo -e "${YELLOW}⚠${NC} Logs directory not found"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Summary
echo "========================================"
echo "Diagnostic Summary"
echo "========================================"
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "If you're still getting 404 errors, check:"
    echo "1. Clear cache: rm -rf cache/* administrator/cache/*"
    echo "2. Restart PHP: systemctl restart php-fpm"
    echo "3. Check database for menu items"
    echo "4. Review logs/com_solidres.routing.php"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ $WARNINGS warning(s) found${NC}"
    echo ""
    echo "Warnings should be reviewed but may not be critical."
    echo "Try clearing cache and restarting PHP."
else
    echo -e "${RED}✗ $ERRORS error(s) and $WARNINGS warning(s) found${NC}"
    echo ""
    echo "CRITICAL ISSUES DETECTED!"
    echo ""
    echo "Recommended actions:"
    echo "1. Copy the complete patches/router.php file:"
    echo "   cp patches/router.php components/com_solidres/router.php"
    echo "2. Clear all caches:"
    echo "   rm -rf cache/* administrator/cache/*"
    echo "3. Restart PHP:"
    echo "   systemctl restart php-fpm"
    echo "4. Check TROUBLESHOOTING_404.md for detailed help"
fi
echo ""

# Database check hint
echo "========================================"
echo "Next Steps"
echo "========================================"
echo "To check for menu items in database, run:"
echo "  mysql -u USER -p DATABASE -e \"SELECT id, title, link FROM joomla_menu WHERE link LIKE '%solidres%';\""
echo ""
echo "To view routing logs in real-time:"
echo "  tail -f logs/com_solidres.routing.php"
echo ""
echo "For complete troubleshooting guide, see:"
echo "  TROUBLESHOOTING_404.md"
echo ""

exit $ERRORS
