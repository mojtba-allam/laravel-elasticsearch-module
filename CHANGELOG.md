# Changelog

All notable changes to this project will be documented in this file.

## [v2.0.0] - 2026-04-05

### Added - Elasticsearch Mapping Management System

#### New Commands
- **elasticsearch:setup-index** - Create Elasticsearch indices from config
  - `--list` option to view all available mappings
  - `--delete` option to recreate existing indices
  - Reads mappings from `config/elasticsearch.php`

- **elasticsearch:add-mapping** - Add new index mappings dynamically
  - Interactive mode for guided mapping creation
  - Command-line mode with `--fields` option
  - `--with-embedding` flag to auto-add vector field

- **elasticsearch:index-status** - Manage Elasticsearch indices
  - `--list` to view all indices with health status
  - `--stats` for cluster statistics
  - `--delete={name}` to remove indices

#### Configuration Files
- `config/elasticsearch.php` - Central mapping definitions
  - Predefined mappings for documents, products, articles, posts, users
  - Configurable index settings
  - Embedding service configuration

- `config/elasticsearch.example.php` - Documented example configuration
  - All field types explained
  - Usage examples
  - Best practices

#### Documentation
- `ELASTICSEARCH_COMMANDS.md` - Quick command reference
  - All commands with examples
  - Common workflows
  - Field types reference

- `ELASTICSEARCH_MAPPINGS_GUIDE.md` - Complete mapping management guide
  - Step-by-step tutorials
  - Common patterns (e-commerce, blog, users)
  - Troubleshooting section
  - Best practices

#### Testing
- `scripts/test-elasticsearch-commands.sh` - Automated test suite
  - Tests all mapping commands
  - Verifies index creation
  - Cleanup after tests

#### Files Changed
- 9 files changed
- 1,523 insertions (+)
- 3 deletions (-)

### Changed
- Updated README.md with mapping management features
- All Elasticsearch mappings now managed via Laravel config files
- Replaced bash script approach with Laravel Artisan commands

### Benefits
- ✅ Version control for index mappings
- ✅ Easy to add new models to search
- ✅ No need to edit bash scripts
- ✅ Consistent mapping definitions across environments
- ✅ Interactive and command-line interfaces
- ✅ Complete documentation and examples

---

## [v1.0.0] - 2026-04-04

### Initial Release

#### Features
- Elasticsearch integration with Laravel
- Vector embeddings for semantic search (384 dimensions)
- Hybrid search (keyword + semantic)
- Automatic document indexing
- Bulk operations support
- Queue-based async processing
- Docker Compose setup
- Python embedding service
- Complete test suite

#### Components
- Search Services (Vector, Hybrid, Keyword)
- Controllers (Search, Document)
- Commands (Seeding)
- Jobs (Async embedding generation)
- Docker configuration
- Installation and test scripts

---

## Repository

GitHub: https://github.com/mojtba-allam/laravel-elasticsearch-module

## License

MIT License
