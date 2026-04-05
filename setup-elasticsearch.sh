#!/bin/bash

# Elasticsearch Setup Script for Semantic Search

echo "Setting up Elasticsearch index for semantic search..."

# Elasticsearch host
ES_HOST="${ELASTICSEARCH_HOST:-http://localhost:9200}"

# Check if Elasticsearch is running
echo "Checking Elasticsearch connection..."
if ! curl -s "$ES_HOST" > /dev/null; then
    echo "Error: Cannot connect to Elasticsearch at $ES_HOST"
    echo "Please ensure Elasticsearch is running."
    exit 1
fi

echo "✓ Elasticsearch is running"

# Delete existing index if it exists
echo "Deleting existing 'documents' index (if exists)..."
curl -X DELETE "$ES_HOST/documents" 2>/dev/null

# Create index with proper mapping
echo "Creating 'documents' index with vector mapping..."
curl -X PUT "$ES_HOST/documents" -H 'Content-Type: application/json' -d '{
  "settings": {
    "number_of_shards": 1,
    "number_of_replicas": 0,
    "analysis": {
      "analyzer": {
        "default": {
          "type": "standard"
        }
      }
    }
  },
  "mappings": {
    "properties": {
      "title": {
        "type": "text",
        "analyzer": "standard"
      },
      "content": {
        "type": "text",
        "analyzer": "standard"
      },
      "embedding": {
        "type": "dense_vector",
        "dims": 384,
        "index": true,
        "similarity": "cosine"
      }
    }
  }
}'

echo ""
echo "✓ Elasticsearch index 'documents' created successfully!"
echo ""
echo "Index configuration:"
echo "  - title: text field with standard analyzer"
echo "  - content: text field with standard analyzer"
echo "  - embedding: dense_vector (384 dims, cosine similarity)"
echo ""
echo "You can now index documents with embeddings."
