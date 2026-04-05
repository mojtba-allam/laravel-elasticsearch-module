<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Index Mappings
    |--------------------------------------------------------------------------
    |
    | Define your Elasticsearch index mappings here. Each key is the index name
    | and the value is the mapping configuration.
    |
    */

    'mappings' => [

        // books index
        'books' => [
            'title' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'author' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'isbn' => [
                'type' => 'keyword',
            ],
            'price' => [
                'type' => 'float',
            ],
            'published_date' => [
                'type' => 'date',
            ],
            'embedding' => [
                'type' => 'dense_vector',
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ],
        ],
        // Documents index
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
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ],
        ],

        // Products index
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
                'type' => 'keyword',
            ],
            'sku' => [
                'type' => 'keyword',
            ],
            'in_stock' => [
                'type' => 'boolean',
            ],
            'tags' => [
                'type' => 'keyword',
            ],
            'embedding' => [
                'type' => 'dense_vector',
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ],
        ],

        // Articles index
        'articles' => [
            'title' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'content' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'author' => [
                'type' => 'keyword',
            ],
            'category' => [
                'type' => 'keyword',
            ],
            'tags' => [
                'type' => 'keyword',
            ],
            'published_at' => [
                'type' => 'date',
            ],
            'views' => [
                'type' => 'integer',
            ],
            'embedding' => [
                'type' => 'dense_vector',
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ],
        ],

        // Posts index
        'posts' => [
            'title' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'content' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'author' => [
                'type' => 'keyword',
            ],
            'category' => [
                'type' => 'keyword',
            ],
            'published_at' => [
                'type' => 'date',
            ],
            'embedding' => [
                'type' => 'dense_vector',
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ],
        ],

        // Users index
        'users' => [
            'name' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'email' => [
                'type' => 'keyword',
            ],
            'bio' => [
                'type' => 'text',
                'analyzer' => 'standard',
            ],
            'location' => [
                'type' => 'keyword',
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
    | Default settings for all indices
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
];
