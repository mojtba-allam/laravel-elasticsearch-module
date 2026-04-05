# Elasticsearch Mappings Management Guide

This guide explains how to manage Elasticsearch index mappings in your Laravel application.

## Overview

Elasticsearch mappings define the structure of your indices, similar to database schemas. They specify field types, analyzers, and other properties that determine how data is indexed and searched.

## Configuration File

All mappings are defined in `config/elasticsearch.php`. This file contains:
- Index mappings for each model
- Default index settings
- Embedding service configuration

## Available Commands

### 1. List Available Mappings

View all configured index mappings:

```bash
php artisan elasticsearch:setup-index --list
```

This shows all indices defined in `config/elasticsearch.php` with their field types.

### 2. Create an Index

Create an Elasticsearch index using a configured mapping:

```bash
php artisan elasticsearch:setup-index {index-name}
```

Example:
```bash
php artisan elasticsearch:setup-index products
```

Options:
- `--delete`: Delete existing index before creating (useful for schema changes)

```bash
php artisan elasticsearch:setup-index products --delete
```

### 3. Add New Mapping

Add a new index mapping to the configuration:

```bash
php artisan elasticsearch:add-mapping {index-name} --fields="field1:type1,field2:type2" --with-embedding
```

Example:
```bash
php artisan elasticsearch:add-mapping books \
  --fields="title:text,author:text,isbn:keyword,price:float,published_date:date" \
  --with-embedding
```

The `--with-embedding` flag automatically adds a 384-dimensional dense_vector field for semantic search.

### 4. Manage Indices

View and manage existing indices:

```bash
# List all indices
php artisan elasticsearch:index-status --list

# Show cluster statistics
php artisan elasticsearch:index-status --stats

# Delete an index
php artisan elasticsearch:index-status --delete=products
```

## Field Types

### Text Fields
For full-text search (titles, descriptions, content):
```php
'title' => [
    'type' => 'text',
    'analyzer' => 'standard',
],
```

### Keyword Fields
For exact matching (categories, tags, emails):
```php
'category' => [
    'type' => 'keyword',
],
```

### Numeric Fields
```php
'price' => ['type' => 'float'],
'quantity' => ['type' => 'integer'],
```

### Boolean Fields
```php
'in_stock' => ['type' => 'boolean'],
```

### Date Fields
```php
'published_at' => ['type' => 'date'],
```

### Vector Fields (for semantic search)
```php
'embedding' => [
    'type' => 'dense_vector',
    'dims' => 384,  // Must match your embedding model
    'index' => true,
    'similarity' => 'cosine',
],
```

## Adding a New Model to Search

### Step 1: Define the Mapping

Option A - Use the command:
```bash
php artisan elasticsearch:add-mapping articles \
  --fields="title:text,content:text,author:keyword,published_at:date" \
  --with-embedding
```

Option B - Edit `config/elasticsearch.php` manually:
```php
'mappings' => [
    'articles' => [
        'title' => ['type' => 'text', 'analyzer' => 'standard'],
        'content' => ['type' => 'text', 'analyzer' => 'standard'],
        'author' => ['type' => 'keyword'],
        'published_at' => ['type' => 'date'],
        'embedding' => [
            'type' => 'dense_vector',
            'dims' => 384,
            'index' => true,
            'similarity' => 'cosine',
        ],
    ],
],
```

### Step 2: Create the Index

```bash
php artisan elasticsearch:setup-index articles
```

### Step 3: Configure Your Model

Add Laravel Scout to your model:

```php
use Laravel\Scout\Searchable;

class Article extends Model
{
    use Searchable;

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->author,
            'published_at' => $this->published_at,
            // embedding will be generated automatically
        ];
    }

    /**
     * Get the index name for the model.
     */
    public function searchableAs()
    {
        return 'articles';
    }
}
```

### Step 4: Index Your Data

```bash
php artisan scout:import "App\Models\Article"
```

Or use the seeding command:
```bash
php artisan documents:seed-with-embeddings --model=Article --count=100
```

## Updating Mappings

Elasticsearch doesn't allow changing field types on existing indices. To update a mapping:

1. Delete the old index:
```bash
php artisan elasticsearch:index-status --delete=products
```

2. Update the mapping in `config/elasticsearch.php`

3. Recreate the index:
```bash
php artisan elasticsearch:setup-index products
```

4. Reindex your data:
```bash
php artisan scout:import "App\Models\Product"
```

## Common Patterns

### E-commerce Products
```php
'products' => [
    'name' => ['type' => 'text', 'analyzer' => 'standard'],
    'description' => ['type' => 'text', 'analyzer' => 'standard'],
    'sku' => ['type' => 'keyword'],
    'price' => ['type' => 'float'],
    'category' => ['type' => 'keyword'],
    'brand' => ['type' => 'keyword'],
    'tags' => ['type' => 'keyword'],
    'in_stock' => ['type' => 'boolean'],
    'rating' => ['type' => 'float'],
    'embedding' => [
        'type' => 'dense_vector',
        'dims' => 384,
        'index' => true,
        'similarity' => 'cosine',
    ],
],
```

### Blog Posts
```php
'posts' => [
    'title' => ['type' => 'text', 'analyzer' => 'standard'],
    'content' => ['type' => 'text', 'analyzer' => 'standard'],
    'excerpt' => ['type' => 'text', 'analyzer' => 'standard'],
    'author' => ['type' => 'keyword'],
    'category' => ['type' => 'keyword'],
    'tags' => ['type' => 'keyword'],
    'published_at' => ['type' => 'date'],
    'views' => ['type' => 'integer'],
    'embedding' => [
        'type' => 'dense_vector',
        'dims' => 384,
        'index' => true,
        'similarity' => 'cosine',
    ],
],
```

### User Profiles
```php
'users' => [
    'name' => ['type' => 'text', 'analyzer' => 'standard'],
    'email' => ['type' => 'keyword'],
    'bio' => ['type' => 'text', 'analyzer' => 'standard'],
    'location' => ['type' => 'keyword'],
    'skills' => ['type' => 'keyword'],
    'joined_at' => ['type' => 'date'],
    'embedding' => [
        'type' => 'dense_vector',
        'dims' => 384,
        'index' => true,
        'similarity' => 'cosine',
    ],
],
```

## Troubleshooting

### Index Already Exists
If you get an error that the index already exists, delete it first:
```bash
php artisan elasticsearch:setup-index products --delete
```

### Mapping Conflict
If you change a field type, you must recreate the index:
```bash
php artisan elasticsearch:index-status --delete=products
php artisan elasticsearch:setup-index products
php artisan scout:import "App\Models\Product"
```

### Check Index Health
```bash
php artisan elasticsearch:index-status --list
```

Look for green status. Yellow means replicas aren't assigned (normal for single-node clusters).

### Verify Mapping
```bash
curl http://localhost:9200/products/_mapping?pretty
```

## Best Practices

1. **Use text for searchable content**: Titles, descriptions, content
2. **Use keyword for filters**: Categories, tags, statuses, IDs
3. **Always include embedding field**: For semantic search capabilities
4. **Keep dimensions consistent**: All embeddings should use 384 dims (for all-MiniLM-L6-v2)
5. **Use meaningful index names**: Match your model names (lowercase, plural)
6. **Version your mappings**: Keep track of mapping changes in version control
7. **Test before production**: Create test indices to verify mappings work as expected

## Environment Variables

Add to your `.env`:

```env
ELASTICSEARCH_HOST=http://localhost:9200
ELASTICSEARCH_SHARDS=1
ELASTICSEARCH_REPLICAS=0
EMBEDDING_SERVICE_URL=http://localhost:8000
EMBEDDING_MODEL=sentence-transformers/all-MiniLM-L6-v2
EMBEDDING_DIMENSIONS=384
```

## Next Steps

1. Define your mappings in `config/elasticsearch.php`
2. Create indices with `php artisan elasticsearch:setup-index`
3. Configure your models with Laravel Scout
4. Index your data with `php artisan scout:import`
5. Start searching!

For more information on Elasticsearch mapping types, see:
https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-types.html
