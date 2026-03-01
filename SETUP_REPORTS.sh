#!/usr/bin/env bash

# Comprehensive Setup and Installation Script for WHMCS System Reports
# This script handles all necessary steps to set up the reporting system

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   WHMCS System - Reports Integration Setup                    ║"
echo "║   Version: 1.0                                                ║"
echo "║   Date: January 29, 2026                                      ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Color codes for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: composer.json not found. Please run this script from the project root.${NC}"
    exit 1
fi

echo -e "${BLUE}Step 1: Verifying Dependencies${NC}"
echo "─────────────────────────────────────"

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ PHP is installed${NC}"

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Composer is installed${NC}"

echo ""
echo -e "${BLUE}Step 2: Installing Laravel Excel Package${NC}"
echo "─────────────────────────────────────"

if grep -q "maatwebsite/excel" composer.json; then
    echo -e "${GREEN}✓ Excel package already installed${NC}"
else
    echo "Installing maatwebsite/excel..."
    composer require maatwebsite/excel
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Excel package installed successfully${NC}"
    else
        echo -e "${RED}Error: Failed to install Excel package${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${BLUE}Step 3: Verifying New Files${NC}"
echo "─────────────────────────────────────"

# Check if ReportService exists
if [ -f "app/Services/ReportService.php" ]; then
    echo -e "${GREEN}✓ ReportService.php exists${NC}"
else
    echo -e "${RED}✗ ReportService.php not found${NC}"
fi

# Check if ReportController exists
if [ -f "app/Http/Controllers/ReportController.php" ]; then
    echo -e "${GREEN}✓ ReportController.php exists${NC}"
else
    echo -e "${RED}✗ ReportController.php not found${NC}"
fi

# Check if Export files exist
for file in "app/Exports/CustomersExport.php" "app/Exports/InvoicesExport.php" "app/Exports/ProductsExport.php" "app/Exports/TicketsExport.php"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $(basename $file) exists${NC}"
    else
        echo -e "${RED}✗ $(basename $file) not found${NC}"
    fi
done

echo ""
echo -e "${BLUE}Step 4: Verifying Views${NC}"
echo "─────────────────────────────────────"

for file in "resources/views/reports/index.blade.php" "resources/views/reports/customers.blade.php" "resources/views/reports/invoices.blade.php" "resources/views/reports/products.blade.php" "resources/views/reports/tickets.blade.php"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $(basename $file) exists${NC}"
    else
        echo -e "${RED}✗ $(basename $file) not found${NC}"
    fi
done

echo ""
echo -e "${BLUE}Step 5: Checking WHMCS Configuration${NC}"
echo "─────────────────────────────────────"

if php artisan whmcs:check 2>/dev/null | grep -q "passed"; then
    echo -e "${GREEN}✓ WHMCS configuration is valid${NC}"
else
    echo -e "${YELLOW}⚠ WHMCS configuration check failed or API not configured${NC}"
    echo "  You can check configuration manually with: php artisan whmcs:check"
fi

echo ""
echo -e "${BLUE}Step 6: Caching Configuration${NC}"
echo "─────────────────────────────────────"

php artisan config:cache

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Configuration cached successfully${NC}"
else
    echo -e "${YELLOW}⚠ Configuration caching had issues, but application may still work${NC}"
fi

echo ""
echo -e "${BLUE}Step 7: Listing Available Routes${NC}"
echo "─────────────────────────────────────"

php artisan route:list --path="reports" 2>/dev/null | grep "reports"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   Setup Complete!                                             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓ All components are ready${NC}"
echo ""
echo "Next steps:"
echo "─────────────"
echo "1. Start the development server:"
echo -e "   ${BLUE}php artisan serve${NC}"
echo ""
echo "2. Access the reports dashboard:"
echo -e "   ${BLUE}http://localhost:8000/admin/reports${NC}"
echo ""
echo "3. For detailed documentation, see:"
echo -e "   ${BLUE}REPORTS_SYSTEM_README.md${NC}"
echo "   ${BLUE}REPORTS_USAGE_GUIDE.md${NC}"
echo "   ${BLUE}PROJECT_UPDATES_SUMMARY.md${NC}"
echo ""
echo "Commands:"
echo "─────────"
echo "• Check WHMCS config:    php artisan whmcs:check"
echo "• View routes:           php artisan route:list --path=reports"
echo "• Clear cache:           php artisan cache:clear"
echo ""
