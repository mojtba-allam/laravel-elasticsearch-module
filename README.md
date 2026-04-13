# Laravel Elasticsearch Module

A production-ready Laravel module for integrating Elasticsearch with support for:
- **Full-text search** with BM25 ranking
- **Semantic search** using vector embeddings
- **Hybrid search** combining keyword and semantic search
- **Easy integration** with any Laravel model
- **Automatic indexing** and embedding generation

## 🚀 Quick Start

```bash
# 1. Clone the module
git clone https://github.com/mojtba-allam/laravel-elasticsearch-module.git

# 2. Run the installation script
cd laravel-elasticsearch-module
bash scripts/install.sh

# 3. Test the module
bash scripts/test.sh
```

## 📋 Features

- ✅ Elasticsearch integration with Laravel
- ✅ Vector embeddings for semantic search (384 dimensions)
- ✅ Hybrid search (keyword + semantic)
- ✅ **Config-based mapping management** - Define index mappings in PHP config files
- ✅ **Artisan commands** - Create, list, and manage indices via CLI
- ✅ **Dynamic mapping creation** - Add new mappings without editing code
- ✅ Automatic document indexing
- ✅ Bulk operations support
- ✅ Queue-based async processing
- ✅ Docker Compose for easy setup
- ✅ Python embedding service included
- ✅ Complete test suite

## 📦 What's Included

- **Search Services**: Vector, Hybrid, and Keyword search
- **Controllers**: Ready-to-use search and document controllers
- **Commands**: Seeding, indexing, and mapping management commands
- **Jobs**: Async embedding generation
- **Config Management**: PHP-based Elasticsearch mapping definitions
- **Docker Setup**: Elasticsearch container configuration
- **Embedding Service**: Python-based HuggingFace transformer service
- **UI**: Optional search interface
- **Documentation**: Complete guides for mappings and commands

## 🔧 Installation

### Prerequisites

- PHP 8.1+
- Laravel 10+
- Docker & Docker Compose
- Python 3.8+
- Composer

### Automatic Installation

```bash
bash scripts/install.sh
```

This script will:
1. Check prerequisites
2. Install PHP dependencies
3. Install Python dependencies
4. Start Elasticsearch
5. Setup Elasticsearch indices
6. Start embedding service
7. Verify installation

### Manual Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed manual installation steps.

## 🎯 Usage

### Mapping Management

```bash
# List all available mappings
php artisan elasticsearch:setup-index --list

# Create an index
php artisan elasticsearch:setup-index products

# Add a new mapping
php artisan elasticsearch:add-mapping books \
  --fields="title:text,author:text,isbn:keyword,price:float" \
  --with-embedding

# View all indices
php artisan elasticsearch:index-status --list

# Delete an index
php artisan elasticsearch:index-status --delete=products
```

See [ELASTICSEARCH_COMMANDS.md](ELASTICSEARCH_COMMANDS.md) for complete command reference.

### Basic Search

```php
use App\Modules\Search\Services\VectorSearchService;

$searchService = app(VectorSearchService::class);
$results = $searchService->search('Laravel framework', 10);
```

### Hybrid Search

```php
use App\Modules\Search\Services\HybridSearchService;

$hybridSearch = app(HybridSearchService::class);
$results = $hybridSearch->search(
    query: 'machine learning',
    size: 10,
    vectorWeight: 0.7,    // 70% semantic
    keywordWeight: 0.3    // 30% keyword
);
```

### Seed Test Data

```bash
php artisan documents:seed-with-embeddings --count=50
```

### API Endpoints

```bash
# Vector search
GET /api/search/vector?q=your+query

# Hybrid search
GET /api/search/hybrid?q=your+query

# Web UI
GET /
```

## 🧪 Testing

Run the complete test suite:

```bash
bash scripts/test.sh
```

This will test:
- ✅ Elasticsearch connection
- ✅ Embedding service
- ✅ Document seeding
- ✅ Vector search
- ✅ Hybrid search
- ✅ API endpoints

## 📖 Documentation

- [Elasticsearch Commands Reference](ELASTICSEARCH_COMMANDS.md) - Quick command reference
- [Elasticsearch Mappings Guide](ELASTICSEARCH_MAPPINGS_GUIDE.md) - Complete mapping management guide
- [Installation Guide](INSTALLATION.md) - Detailed installation steps
- [Configuration Guide](CONFIGURATION.md) - How to configure for your models
- [API Documentation](API.md) - API endpoints and usage
- [Troubleshooting](TROUBLESHOOTING.md) - Common issues and solutions

## 🔧 Configuration

### Add to Your Laravel Project

1. Copy module files to your project:
```bash
cp -r app/Modules/Search your-laravel-project/app/Modules/
cp -r app/Console/Commands/* your-laravel-project/app/Console/Commands/
cp config/elasticsearch.php your-laravel-project/config/
```

2. Update your `.env`:
```env
SCOUT_DRIVER=elasticsearch
SCOUT_ELASTICSEARCH_HOST=http://localhost:9200
EMBEDDING_SERVICE_URL=http://localhost:8000
ELASTICSEARCH_SHARDS=1
ELASTICSEARCH_REPLICAS=0
```

3. Define your index mappings in `config/elasticsearch.php`:
```php
'mappings' => [
    'products' => [
        'name' => ['type' => 'text', 'analyzer' => 'standard'],
        'description' => ['type' => 'text', 'analyzer' => 'standard'],
        'price' => ['type' => 'float'],
        'category' => ['type' => 'keyword'],
        'embedding' => [
            'type' => 'dense_vector',
            'dims' => 384,
            'index' => true,
            'similarity' => 'cosine',
        ],
    ],
],
```

4. Create indices:
```bash
php artisan elasticsearch:setup-index products
```

5. Add routes to `routes/api.php`:
```php
use App\Modules\Search\Controllers\SearchController;

Route::get('/search/vector', [SearchController::class, 'vector']);
Route::get('/search/hybrid', [SearchController::class, 'hybrid']);
```

6. Start services:
```bash
docker-compose up -d
python3 embedding_server_simple.py &
php artisan queue:work &
```

## 🏗️ Architecture

```
┌─────────────────┐
│  Laravel App    │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
┌───▼──┐  ┌──▼────────┐
│  ES  │  │ Embedding │
│      │  │  Service  │
└──────┘  └───────────┘
```

- **Laravel**: Main application with search services
- **Elasticsearch**: Document storage and search engine
- **Embedding Service**: Python service for generating vector embeddings

## 📊 Performance

- **Embedding Generation**: ~60ms per document
- **Search Latency**: <50ms
- **Bulk Indexing**: 50 docs in ~4.5 seconds
- **Model**: sentence-transformers/all-MiniLM-L6-v2 (384 dims)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

- 📧 Email: support@example.com
- 🐛 Issues: [GitHub Issues](https://github.com/mojtba-allam/laravel-elasticsearch-module/issues)
- 📖 Docs: [Full Documentation](https://github.com/mojtba-allam/laravel-elasticsearch-module/wiki)

## 🙏 Credits

- Built with [Laravel](https://laravel.com)
- Powered by [Elasticsearch](https://www.elastic.co)
- Embeddings by [Sentence Transformers](https://www.sbert.net)

---

**Made with ❤️ for Laravel developers**
