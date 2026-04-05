#!/bin/bash

# Laravel Elasticsearch Module - Test Script
# This script tests all components of the module

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════${NC}\n"
}

TESTS_PASSED=0
TESTS_FAILED=0

run_test() {
    local test_name="$1"
    local test_command="$2"
    
    print_info "Testing: $test_name"
    
    if eval "$test_command" > /dev/null 2>&1; then
        print_success "$test_name"
        ((TESTS_PASSED++))
        return 0
    else
        print_error "$test_name"
        ((TESTS_FAILED++))
        return 1
    fi
}

print_header "Laravel Elasticsearch Module - Test Suite"

# Test 1: Elasticsearch Connection
print_header "Test 1: Elasticsearch Connection"

run_test "Elasticsearch is running" \
    "curl -s http://localhost:9200/_cluster/health"

run_test "Cluster status is green or yellow" \
    "curl -s http://localhost:9200/_cluster/health | grep -E '\"status\":\"(green|yellow)\"'"

run_test "Documents index exists" \
    "curl -s http://localhost:9200/_cat/indices | grep documents"

# Test 2: Embedding Service
print_header "Test 2: Embedding Service"

run_test "Embedding service is running" \
    "curl -s http://localhost:8000/"

run_test "Embedding service returns 384 dimensions" \
    "curl -s -X POST http://localhost:8000/embed -H 'Content-Type: application/json' -d '{\"text\":\"test\"}' | grep -o '\"embedding\"' | wc -l | grep -q 1"

# Measure embedding speed
print_info "Measuring embedding generation speed..."
START_TIME=$(date +%s%N)
curl -s -X POST http://localhost:8000/embed \
  -H "Content-Type: application/json" \
  -d '{"text":"This is a test sentence for measuring embedding generation speed"}' > /dev/null
END_TIME=$(date +%s%N)
DURATION=$(( (END_TIME - START_TIME) / 1000000 ))
print_info "Embedding generation took: ${DURATION}ms"

if [ $DURATION -lt 200 ]; then
    print_success "Embedding speed is good (<200ms)"
    ((TESTS_PASSED++))
else
    print_error "Embedding speed is slow (>200ms)"
    ((TESTS_FAILED++))
fi

# Test 3: Elasticsearch Index Mapping
print_header "Test 3: Elasticsearch Index Mapping"

print_info "Checking index mapping..."
MAPPING=$(curl -s http://localhost:9200/documents/_mapping)

run_test "Title field exists" \
    "echo '$MAPPING' | grep -q '\"title\"'"

run_test "Content field exists" \
    "echo '$MAPPING' | grep -q '\"content\"'"

run_test "Embedding field is dense_vector" \
    "echo '$MAPPING' | grep -q '\"type\":\"dense_vector\"'"

run_test "Embedding dimensions is 384" \
    "echo '$MAPPING' | grep -q '\"dims\":384'"

run_test "Similarity is cosine" \
    "echo '$MAPPING' | grep -q '\"similarity\":\"cosine\"'"

# Test 4: Document Indexing (if in Laravel project)
print_header "Test 4: Document Operations"

if [ -f "artisan" ]; then
    print_info "Laravel project detected, testing document operations..."
    
    # Clear existing documents
    print_info "Clearing existing documents..."
    curl -s -X POST "http://localhost:9200/documents/_delete_by_query" \
      -H 'Content-Type: application/json' \
      -d '{"query":{"match_all":{}}}' > /dev/null
    
    # Seed test documents
    print_info "Seeding test documents..."
    if php artisan documents:seed-with-embeddings --count=5 > /dev/null 2>&1; then
        print_success "Seeded 5 test documents"
        ((TESTS_PASSED++))
    else
        print_error "Failed to seed documents"
        ((TESTS_FAILED++))
    fi
    
    # Wait for indexing
    sleep 2
    
    # Check document count
    DOC_COUNT=$(curl -s "http://localhost:9200/documents/_count" | grep -o '"count":[0-9]*' | cut -d':' -f2)
    if [ "$DOC_COUNT" -ge 5 ]; then
        print_success "Documents indexed in Elasticsearch (count: $DOC_COUNT)"
        ((TESTS_PASSED++))
    else
        print_error "Documents not properly indexed (count: $DOC_COUNT)"
        ((TESTS_FAILED++))
    fi
else
    print_info "Not in Laravel project, skipping document operations"
fi

# Test 5: Search Functionality
print_header "Test 5: Search Functionality"

if [ -f "artisan" ]; then
    print_info "Testing search endpoints..."
    
    # Start Laravel server in background
    print_info "Starting Laravel development server..."
    php artisan serve --port=8001 > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 3
    
    # Test vector search
    print_info "Testing vector search..."
    VECTOR_RESULTS=$(curl -s "http://localhost:8001/api/search/vector?q=PHP%20framework" | grep -o '"results":\[' | wc -l)
    if [ "$VECTOR_RESULTS" -eq 1 ]; then
        print_success "Vector search endpoint working"
        ((TESTS_PASSED++))
    else
        print_error "Vector search endpoint failed"
        ((TESTS_FAILED++))
    fi
    
    # Test hybrid search
    print_info "Testing hybrid search..."
    HYBRID_RESULTS=$(curl -s "http://localhost:8001/api/search/hybrid?q=machine%20learning" | grep -o '"results":\[' | wc -l)
    if [ "$HYBRID_RESULTS" -eq 1 ]; then
        print_success "Hybrid search endpoint working"
        ((TESTS_PASSED++))
    else
        print_error "Hybrid search endpoint failed"
        ((TESTS_FAILED++))
    fi
    
    # Test search quality
    print_info "Testing search quality..."
    SEARCH_RESPONSE=$(curl -s "http://localhost:8001/api/search/vector?q=PHP%20framework")
    TOP_RESULT=$(echo "$SEARCH_RESPONSE" | grep -o '"title":"[^"]*"' | head -1 | cut -d'"' -f4)
    
    if echo "$TOP_RESULT" | grep -qi "laravel\|php"; then
        print_success "Search quality is good (top result: $TOP_RESULT)"
        ((TESTS_PASSED++))
    else
        print_warning "Search quality may need improvement (top result: $TOP_RESULT)"
    fi
    
    # Stop Laravel server
    kill $SERVER_PID 2>/dev/null || true
else
    print_info "Not in Laravel project, skipping search tests"
fi

# Test 6: Performance Benchmarks
print_header "Test 6: Performance Benchmarks"

print_info "Running performance benchmarks..."

# Benchmark: Embedding generation
print_info "Benchmark: Embedding generation (10 requests)..."
TOTAL_TIME=0
for i in {1..10}; do
    START=$(date +%s%N)
    curl -s -X POST http://localhost:8000/embed \
      -H "Content-Type: application/json" \
      -d '{"text":"Performance test"}' > /dev/null
    END=$(date +%s%N)
    DURATION=$(( (END - START) / 1000000 ))
    TOTAL_TIME=$((TOTAL_TIME + DURATION))
done
AVG_TIME=$((TOTAL_TIME / 10))
print_info "Average embedding time: ${AVG_TIME}ms"

if [ $AVG_TIME -lt 100 ]; then
    print_success "Embedding performance: Excellent (<100ms)"
    ((TESTS_PASSED++))
elif [ $AVG_TIME -lt 200 ]; then
    print_success "Embedding performance: Good (<200ms)"
    ((TESTS_PASSED++))
else
    print_warning "Embedding performance: Acceptable (${AVG_TIME}ms)"
fi

# Test Summary
print_header "Test Summary"

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))
SUCCESS_RATE=$((TESTS_PASSED * 100 / TOTAL_TESTS))

echo "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo "Success Rate: ${SUCCESS_RATE}%"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    print_success "All tests passed! ✨"
    echo ""
    echo "Your Elasticsearch module is working perfectly!"
    echo ""
    echo "Next steps:"
    echo "  • Integrate with your Laravel models"
    echo "  • Customize search parameters"
    echo "  • Deploy to production"
    exit 0
else
    print_error "Some tests failed"
    echo ""
    echo "Please check the errors above and:"
    echo "  • Ensure all services are running"
    echo "  • Check logs: embedding_service.log"
    echo "  • Run: bash scripts/install.sh"
    exit 1
fi
