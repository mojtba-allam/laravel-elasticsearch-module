# Elasticsearch Management Commands

Quick reference for all Elasticsearch management commands in this Laravel application.

## Index Management

### List All Available Mappings
```bash
php artisan elasticsearch:setup-index --list
```
Shows all index mappings defined in `config/elasticsearch.php`

### Create an Index
```bash
php artisan elasticsearch:setup-index {index-name}
```
Example: `php artisan elasticsearch:setup-index products`

### Create Index (Delete if Exists)
```bash
php artisan elasticsearch:setup-index {index-name} --delete
```
Useful when updating mappings

### View All Indices
```bash
php artisan elasticsearch:index-status --list
```
Shows all indices with health, status, document count, and size

### View Cluster Statistics
```bash
php artisan elasticsearch:index-status --stats
```
Shows cluster health, nodes, shards, and total documents

### View Everything
```bash
php artisan elasticsearch:index-status
```
Shows both indices list and cluster statistics

### Delete an Index
```bash
php artisan elasticsearch:index-status --delete={index-name}
```
Example: `php artisan elasticsearch:index-status --delete=products`

## Mapping Management

### Add New Mapping (Interactive)
```bash
php artisan elasticsearch:add-mapping {index-name}
```
Prompts for field names and types interactively

### Add New Mapping (Command Line)
```bash
php artisan elasticsearch:add-mapping {index-name} --fields="field1:type1,field2:type2"
```
Example:
```bash
php artisan elasticsearch:add-mapping books --fields="title:text,author:text,isbn:keyword,price:float"
```

### Add Mapping with Embedding Field
```bash
php artisan elasticsearch:add-mapping {index-name} --fields="..." --with-embedding
```
Automatically adds a 384-dimensional dense_vector field

## Data Seeding

### Seed Documents with Embeddings
```bash
php artisan documents:seed-with-embeddings --count={number}
```
Example: `php artisan documents:seed-with-embeddings --count=100`

## Laravel Scout Commands

### Import All Models
```bash
php artisan scout:import "App\Models\{ModelName}"
```
Example: `php artisan scout:import "App\Models\Product"`

### Flush Index
```bash
php artisan scout:flush "App\Models\{ModelName}"
```
Removes all documents from the index

## Common Workflows

### Adding a New Searchable Model

1. Add mapping:
```bash
php artisan elasticsearch:add-mapping articles \
  --fields="title:text,content:text,author:keyword,published_at:date" \
  --with-embedding
```

2. Create index:
```bash
php artisan elasticsearch:setup-index articles
```

3. Configure model (add Searchable trait)

4. Import data:
```bash
php artisan scout:import "App\Models\Article"
```

### Updating a Mapping

1. Delete old index:
```bash
php artisan elasticsearch:index-status --delete=products
```

2. Edit `config/elasticsearch.php`

3. Recreate index:
```bash
php artisan elasticsearch:setup-index products
```

4. Reindex data:
```bash
php artisan scout:import "App\Models\Product"
```

### Checking System Health

```bash
# View all indices
php artisan elasticsearch:index-status --list

# View cluster stats
php artisan elasticsearch:index-status --stats

# Check Elasticsearch directly
curl http://localhost:9200/_cluster/health?pretty
```

## Field Types Reference

| Type | Use Case | Example |
|------|----------|---------|
| `text` | Full-text search | title, description, content |
| `keyword` | Exact match, filtering | email, category, tags, status |
| `integer` | Whole numbers | quantity, views, likes |
| `float` | Decimal numbers | price, rating, score |
| `boolean` | True/false | in_stock, is_active, featured |
| `date` | Dates/timestamps | published_at, created_at |
| `dense_vector` | Semantic search | embedding (384 dims) |

## Tips

- Always use `--with-embedding` for semantic search capabilities
- Use `text` for searchable content, `keyword` for filters
- Check index health regularly with `--list`
- Delete and recreate indices when changing field types
- Keep embedding dimensions at 384 (for all-MiniLM-L6-v2 model)

## Configuration Files

- `config/elasticsearch.php` - Index mappings and settings
- `config/scout.php` - Laravel Scout configuration
- `.env` - Elasticsearch connection settings

## Environment Variables

```env
ELASTICSEARCH_HOST=http://localhost:9200
ELASTICSEARCH_SHARDS=1
ELASTICSEARCH_REPLICAS=0
EMBEDDING_SERVICE_URL=http://localhost:8000
EMBEDDING_DIMENSIONS=384
```
