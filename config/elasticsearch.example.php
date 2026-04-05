<?php

/**
 * Elasticsearch Configuration Example
 * 
 * This file shows how to configure Elasticsearch index mappings for your Laravel models.
 * Copy this to config/elasticsearch.php and customize for your needs.
 * 
 * ADDING NEW MAPPINGS:
 * 
 * Option 1: Use the artisan command (recommended)
 *   php artisan elasticsearch:add-mapping {index-name} --fields="field1:type1,field2:type2" --with-embedding
 * 
 * Option 2: Manually edit this file
 *   Add a new entry to the 'mappings' array below
 * 
 * FIELD TYPES:
 * - text: Full-text searchable fields (e.g., title, description)
 * - keyword: Exact match fields (e.g., email, category, tags)
 * - integer: Whole numbers
 * - float: Decimal numbers
 * - boolean: true/false values
 * - date: Date/datetime fields
 * - dense_vector: Vector embeddings for semantic search (384 dimensions for all-MiniLM-L6-v2)
 * 
 * CREATING INDICES:
 *   php artisan elasticsearch:setup-index {index-name}
 * 
 * LISTING AVAILABLE MAPPINGS:
 *   php artisan elasticsearch:setup-index --list
 * 
 * MANAGING INDICES:
 *   php artisan elasticsearch:index-status --list
 *   php artisan elasticsearch:index-status --stats
 *   php artisan elasticsearch:index-status --delete={index-name}
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Index Mappings
    |--------------------------------------------------------------------------
    |
    | Define your Elasticsearch index mappings here. Each key is the index name
    | and the value is the mapping configuration.
    |
    | The index name should match your model's searchableAs() method return value.
    |
    */

    'mappings' => [
        
        // Example: Documents index
        'documents' => [
            'title' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'content' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'embedding' => [
                'type' => 'dense_vector',
                'dims' => 384,  // Must match your embedding model dimensions
                'index' => true,
                'similarity' => 'cosine',  // Options: cosine, dot_product, l2_norm
            ],
        ],

        // Example: Products index with various field types
        'products' => [
            'name' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'description' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'price' => [
                'type' => 'float',
            ],
            'category' => [
                'type' => 'keyword',  // Use keyword for exact matching
            ],
            'sku' => [
                'type' => 'keyword',
            ],
            'in_stock' => [
                'type' => 'boolean',
            ],
            'tags' => [
                'type' => 'keyword',  // Array of keywords
            ],
            'embedding' => [
                'type' => 'dense_vector',
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Index Settings
    |--------------------------------------------------------------------------
    |
    | Default settings applied to all indices when created.
    |
    */

    'settings' => [
        'number_of_shards' => env('ELASTICSEARCH_SHARDS', 1),
        'number_of_replicas' => env('ELASTICSEARCH_REPLICAS', 0),
        'analysis' => [
            'analyzer' => [
                'default' => [
                    'type' => 'standard'
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the embedding service used for vector search.
    |
    */

    'embedding' => [
        'service_url' => env('EMBEDDING_SERVICE_URL', 'http://localhost:8000'),
        'model' => env('EMBEDDING_MODEL', 'sentence-transformers/all-MiniLM-L6-v2'),
        'dimensions' => env('EMBEDDING_DIMENSIONS', 384),
        'timeout' => env('EMBEDDING_TIMEOUT', 30),
    ],
];
