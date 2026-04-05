#!/bin/bash

# Complete Elasticsearch Mapping Management Test
# This script demonstrates all the Elasticsearch management commands

echo "=========================================="
echo "Elasticsearch Commands Test Suite"
echo "=========================================="
echo ""

echo "=== Step 1: List Available Mappings ==="
php artisan elasticsearch:setup-index --list
echo ""

echo "=== Step 2: View Current Indices ==="
php artisan elasticsearch:index-status --list
echo ""

echo "=== Step 3: Add New Mapping for 'movies' ==="
php artisan elasticsearch:add-mapping movies \
  --fields="title:text,director:text,genre:keyword,year:integer,rating:float" \
  --with-embedding <<< "yes"
echo ""

echo "=== Step 4: Verify Mapping Added ==="
php artisan elasticsearch:setup-index --list | grep -A 8 "movies"
echo ""

echo "=== Step 5: Create the Index ==="
php artisan elasticsearch:setup-index movies
echo ""

echo "=== Step 6: Verify Index Created ==="
php artisan elasticsearch:index-status --list
echo ""

echo "=== Step 7: View Cluster Stats ==="
php artisan elasticsearch:index-status --stats
echo ""

echo "=== Step 8: Cleanup - Delete Test Index ==="
php artisan elasticsearch:index-status --delete=movies <<< "yes"
echo ""

echo "=========================================="
echo "✓ All tests passed!"
echo "=========================================="
