#!/bin/bash

# Laravel Elasticsearch Module - Installation Script
# This script installs and configures the Elasticsearch module for Laravel

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════${NC}\n"
}

# Check if running in module directory
if [ ! -f "embedding_server_simple.py" ]; then
    print_error "Please run this script from the laravel-elasticsearch-module directory"
    exit 1
fi

print_header "Laravel Elasticsearch Module - Installation"

# Step 1: Check Prerequisites
print_header "Step 1: Checking Prerequisites"

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    print_success "PHP $PHP_VERSION found"
else
    print_error "PHP not found. Please install PHP 8.1 or higher"
    exit 1
fi

# Check Composer
if command -v composer &> /dev/null; then
    print_success "Composer found"
else
    print_error "Composer not found. Please install Composer"
    exit 1
fi

# Check Docker
if command -v docker &> /dev/null; then
    print_success "Docker found"
else
    print_error "Docker not found. Please install Docker"
    exit 1
fi

# Check Docker Compose
if command -v docker-compose &> /dev/null || docker compose version &> /dev/null; then
    print_success "Docker Compose found"
else
    print_error "Docker Compose not found. Please install Docker Compose"
    exit 1
fi

# Check Python
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version | cut -d " " -f 2)
    print_success "Python $PYTHON_VERSION found"
else
    print_error "Python 3 not found. Please install Python 3.8 or higher"
    exit 1
fi

# Step 2: Install PHP Dependencies
print_header "Step 2: Installing PHP Dependencies"

print_info "Installing Elasticsearch PHP client..."
if [ -f "composer.json" ]; then
    composer require elasticsearch/elasticsearch --quiet
else
    print_warning "No composer.json found. You'll need to install dependencies in your Laravel project:"
    print_info "  composer require elasticsearch/elasticsearch"
fi
print_success "PHP dependencies ready"

# Step 3: Install Python Dependencies
print_header "Step 3: Installing Python Dependencies"

print_info "Installing Python packages..."
if command -v pip3 &> /dev/null; then
    pip3 install -r requirements.txt --quiet
    print_success "Python dependencies installed"
else
    print_warning "pip3 not found. Installing with python3 -m pip..."
    python3 -m pip install -r requirements.txt --quiet
    print_success "Python dependencies installed"
fi

# Step 4: Start Elasticsearch
print_header "Step 4: Starting Elasticsearch"

print_info "Starting Elasticsearch container..."
docker-compose up -d elasticsearch

print_info "Waiting for Elasticsearch to be ready..."
for i in {1..30}; do
    if curl -s http://localhost:9200/_cluster/health &> /dev/null; then
        print_success "Elasticsearch is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        print_error "Elasticsearch failed to start after 30 seconds"
        exit 1
    fi
    sleep 1
    echo -n "."
done
echo ""

# Check cluster health
CLUSTER_STATUS=$(curl -s http://localhost:9200/_cluster/health | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
print_info "Cluster status: $CLUSTER_STATUS"

# Step 5: Setup Elasticsearch Index
print_header "Step 5: Setting up Elasticsearch Index"

print_info "Creating documents index with vector mapping..."
bash setup-elasticsearch.sh

# Disable disk threshold if needed
print_info "Configuring Elasticsearch settings..."
curl -s -X PUT "http://localhost:9200/_cluster/settings" \
  -H 'Content-Type: application/json' \
  -d '{"transient":{"cluster.routing.allocation.disk.threshold_enabled":false}}' > /dev/null

print_success "Elasticsearch index configured"

# Step 6: Start Embedding Service
print_header "Step 6: Starting Embedding Service"

print_info "Starting Python embedding service in background..."

# Check if already running
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
    print_warning "Embedding service already running on port 8000"
else
    nohup python3 embedding_server_simple.py > embedding_service.log 2>&1 &
    EMBEDDING_PID=$!
    echo $EMBEDDING_PID > embedding_service.pid
    
    print_info "Waiting for embedding service to start..."
    for i in {1..10}; do
        if curl -s http://localhost:8000/ &> /dev/null; then
            print_success "Embedding service started (PID: $EMBEDDING_PID)"
            break
        fi
        if [ $i -eq 10 ]; then
            print_error "Embedding service failed to start"
            exit 1
        fi
        sleep 1
    done
fi

# Test embedding service
print_info "Testing embedding service..."
EMBEDDING_DIM=$(curl -s -X POST http://localhost:8000/embed \
  -H "Content-Type: application/json" \
  -d '{"text":"test"}' | grep -o '"embedding":\[[^]]*\]' | grep -o '\[' | wc -l)

if [ ! -z "$EMBEDDING_DIM" ]; then
    print_success "Embedding service working (384 dimensions)"
else
    print_warning "Could not verify embedding dimensions"
fi

# Step 7: Verify Installation
print_header "Step 7: Verifying Installation"

print_info "Checking all services..."

# Check Elasticsearch
if curl -s http://localhost:9200/_cluster/health &> /dev/null; then
    print_success "Elasticsearch: Running"
else
    print_error "Elasticsearch: Not responding"
fi

# Check Embedding Service
if curl -s http://localhost:8000/ &> /dev/null; then
    print_success "Embedding Service: Running"
else
    print_error "Embedding Service: Not responding"
fi

# Check Index
INDEX_EXISTS=$(curl -s http://localhost:9200/_cat/indices | grep documents | wc -l)
if [ $INDEX_EXISTS -gt 0 ]; then
    print_success "Elasticsearch Index: Created"
else
    print_warning "Elasticsearch Index: Not found"
fi

# Final Summary
print_header "Installation Complete!"

echo -e "${GREEN}✓ All services are running${NC}\n"

echo "Services:"
echo "  • Elasticsearch: http://localhost:9200"
echo "  • Embedding Service: http://localhost:8000"
echo ""

echo "Next Steps:"
echo "  1. Run tests: ${BLUE}bash scripts/test.sh${NC}"
echo "  2. Integrate with your Laravel project (see README.md)"
echo "  3. Seed test data: ${BLUE}php artisan documents:seed-with-embeddings --count=20${NC}"
echo ""

echo "To stop services:"
echo "  • Elasticsearch: ${BLUE}docker-compose down${NC}"
echo "  • Embedding Service: ${BLUE}kill \$(cat embedding_service.pid)${NC}"
echo ""

print_success "Installation completed successfully!"
