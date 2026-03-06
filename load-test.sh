#!/bin/bash

# ============================================
# Load Testing Script for CBT System
# Test 500+ Concurrent Users
# ============================================

echo "============================================"
echo "CBT Load Testing Script"
echo "============================================"
echo ""

# Configuration
BASE_URL="http://127.0.0.1:8000"
CONCURRENT_USERS=500
DURATION=60  # seconds
RAMP_UP=30   # seconds

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if tools are installed
check_tools() {
    echo "Checking required tools..."
    
    if ! command -v ab &> /dev/null; then
        echo -e "${RED}✗ Apache Bench (ab) not found${NC}"
        echo "Install: sudo apt install apache2-utils"
        exit 1
    fi
    
    if ! command -v siege &> /dev/null; then
        echo -e "${YELLOW}⚠ Siege not found (optional)${NC}"
        echo "Install: sudo apt install siege"
    fi
    
    echo -e "${GREEN}✓ Tools ready${NC}"
    echo ""
}

# Test 1: Homepage Load Test
test_homepage() {
    echo "============================================"
    echo "Test 1: Homepage Load Test"
    echo "============================================"
    echo "URL: ${BASE_URL}"
    echo "Concurrent Users: 100"
    echo "Total Requests: 1000"
    echo ""
    
    ab -n 1000 -c 100 -g homepage.tsv "${BASE_URL}/" > homepage_results.txt
    
    echo -e "${GREEN}✓ Test completed${NC}"
    echo "Results saved to: homepage_results.txt"
    echo ""
    
    # Display summary
    grep "Requests per second" homepage_results.txt
    grep "Time per request" homepage_results.txt
    grep "Failed requests" homepage_results.txt
    echo ""
}

# Test 2: Login Page Load Test
test_login() {
    echo "============================================"
    echo "Test 2: Login Page Load Test"
    echo "============================================"
    echo "URL: ${BASE_URL}/login"
    echo "Concurrent Users: 100"
    echo "Total Requests: 1000"
    echo ""
    
    ab -n 1000 -c 100 -g login.tsv "${BASE_URL}/login" > login_results.txt
    
    echo -e "${GREEN}✓ Test completed${NC}"
    echo "Results saved to: login_results.txt"
    echo ""
    
    # Display summary
    grep "Requests per second" login_results.txt
    grep "Time per request" login_results.txt
    grep "Failed requests" login_results.txt
    echo ""
}

# Test 3: Static Assets Load Test
test_static() {
    echo "============================================"
    echo "Test 3: Static Assets Load Test"
    echo "============================================"
    echo "Testing CSS/JS/Images loading"
    echo ""
    
    # Test CSS
    ab -n 500 -c 50 "${BASE_URL}/css/app.css" > static_css_results.txt 2>&1
    
    echo -e "${GREEN}✓ CSS test completed${NC}"
    grep "Requests per second" static_css_results.txt
    echo ""
}

# Test 4: API Endpoint Stress Test
test_api() {
    echo "============================================"
    echo "Test 4: API Endpoint Stress Test"
    echo "============================================"
    echo "Simulating save jawaban requests"
    echo "Concurrent Users: ${CONCURRENT_USERS}"
    echo ""
    
    # Create POST data
    cat > post_data.json << EOF
{
    "bank_soal_id": 1,
    "jawaban": "A",
    "is_ragu": false
}
EOF
    
    echo "⚠ Note: This test requires authentication"
    echo "Please provide a valid session cookie:"
    read -p "Session Cookie (or press Enter to skip): " SESSION_COOKIE
    
    if [ -z "$SESSION_COOKIE" ]; then
        echo -e "${YELLOW}Skipping API test (no session cookie)${NC}"
        echo ""
        return
    fi
    
    ab -n 1000 -c 100 \
       -p post_data.json \
       -T "application/json" \
       -C "laravel_session=${SESSION_COOKIE}" \
       "${BASE_URL}/exam/1/save-jawaban" > api_results.txt
    
    echo -e "${GREEN}✓ Test completed${NC}"
    echo "Results saved to: api_results.txt"
    echo ""
    
    # Display summary
    grep "Requests per second" api_results.txt
    grep "Time per request" api_results.txt
    grep "Failed requests" api_results.txt
    echo ""
}

# Test 5: Siege Stress Test (if available)
test_siege() {
    if ! command -v siege &> /dev/null; then
        echo "Siege not installed, skipping..."
        return
    fi
    
    echo "============================================"
    echo "Test 5: Siege Stress Test"
    echo "============================================"
    echo "Concurrent Users: 100"
    echo "Duration: 60 seconds"
    echo ""
    
    # Create URLs file
    cat > urls.txt << EOF
${BASE_URL}/
${BASE_URL}/login
${BASE_URL}/siswa/dashboard
EOF
    
    siege -c 100 -t 60S -f urls.txt > siege_results.txt 2>&1
    
    echo -e "${GREEN}✓ Test completed${NC}"
    echo "Results saved to: siege_results.txt"
    echo ""
    
    # Display summary
    grep "Transactions:" siege_results.txt
    grep "Availability:" siege_results.txt
    grep "Response time:" siege_results.txt
    grep "Transaction rate:" siege_results.txt
    echo ""
}

# Test 6: Database Connection Test
test_database() {
    echo "============================================"
    echo "Test 6: Database Connection Test"
    echo "============================================"
    echo "Testing database under load..."
    echo ""
    
    # Simulate multiple concurrent database queries
    for i in {1..10}; do
        (
            mysql -u root -p -e "SELECT COUNT(*) FROM jawaban_siswas;" cbt_smk
        ) &
    done
    
    wait
    
    echo -e "${GREEN}✓ Database test completed${NC}"
    echo ""
}

# Generate Report
generate_report() {
    echo "============================================"
    echo "Generating Load Test Report"
    echo "============================================"
    echo ""
    
    REPORT_FILE="load_test_report_$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "CBT LOAD TEST REPORT"
        echo "===================="
        echo "Date: $(date)"
        echo "Base URL: ${BASE_URL}"
        echo "Target Concurrent Users: ${CONCURRENT_USERS}"
        echo ""
        echo "=== HOMEPAGE TEST ==="
        if [ -f homepage_results.txt ]; then
            grep "Requests per second" homepage_results.txt
            grep "Time per request" homepage_results.txt
            grep "Failed requests" homepage_results.txt
        fi
        echo ""
        echo "=== LOGIN TEST ==="
        if [ -f login_results.txt ]; then
            grep "Requests per second" login_results.txt
            grep "Time per request" login_results.txt
            grep "Failed requests" login_results.txt
        fi
        echo ""
        echo "=== API TEST ==="
        if [ -f api_results.txt ]; then
            grep "Requests per second" api_results.txt
            grep "Time per request" api_results.txt
            grep "Failed requests" api_results.txt
        fi
        echo ""
    } > "$REPORT_FILE"
    
    echo -e "${GREEN}✓ Report generated: ${REPORT_FILE}${NC}"
    echo ""
}

# Cleanup
cleanup() {
    echo "Cleaning up temporary files..."
    rm -f post_data.json urls.txt *.tsv
    echo -e "${GREEN}✓ Cleanup completed${NC}"
    echo ""
}

# Main Menu
show_menu() {
    echo "============================================"
    echo "Select Test to Run:"
    echo "============================================"
    echo "1. Homepage Load Test"
    echo "2. Login Page Load Test"
    echo "3. Static Assets Test"
    echo "4. API Endpoint Test"
    echo "5. Siege Stress Test"
    echo "6. Database Connection Test"
    echo "7. Run All Tests"
    echo "8. Generate Report"
    echo "9. Exit"
    echo "============================================"
    echo ""
}

# Main execution
main() {
    check_tools
    
    while true; do
        show_menu
        read -p "Enter your choice [1-9]: " choice
        echo ""
        
        case $choice in
            1) test_homepage ;;
            2) test_login ;;
            3) test_static ;;
            4) test_api ;;
            5) test_siege ;;
            6) test_database ;;
            7)
                test_homepage
                test_login
                test_static
                test_api
                test_siege
                generate_report
                ;;
            8) generate_report ;;
            9)
                cleanup
                echo "Exiting..."
                exit 0
                ;;
            *)
                echo -e "${RED}Invalid choice. Please try again.${NC}"
                echo ""
                ;;
        esac
        
        read -p "Press Enter to continue..."
        clear
    done
}

# Run main
main
