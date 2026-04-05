<?php

namespace App\Console\Commands;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class ElasticsearchSetupIndex extends Command
{
    protected $signature = 'elasticsearch:setup-index 
                            {index? : The index name (e.g., products, articles, posts)}
                            {--delete : Delete existing index before creating}
                            {--list : List all available index mappings}';
    
    protected $description = 'Create Elasticsearch index with mapping from config/elasticsearch.php';

    public function handle()
    {
        // List available mappings if requested
        if ($this->option('list')) {
            return $this->listAvailableMappings();
        }

        $indexName = $this->argument('index');
        
        if (!$indexName) {
            $this->error('Please provide an index name or use --list to see available mappings.');
            return Command::FAILURE;
        }

        $deleteExisting = $this->option('delete');

        $this->info("Setting up Elasticsearch index: {$indexName}");
        $this->newLine();

        // Initialize Elasticsearch client
        $host = config('scout.elasticsearch.hosts.0', 'http://localhost:9200');
        $client = ClientBuilder::create()->setHosts([$host])->build();

        // Check connection
        try {
            $client->ping();
            $this->info('✓ Connected to Elasticsearch');
        } catch (\Exception $e) {
            $this->error('✗ Cannot connect to Elasticsearch');
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Delete existing index if requested
        if ($deleteExisting) {
            try {
                $client->indices()->delete(['index' => $indexName]);
                $this->info("✓ Deleted existing index: {$indexName}");
            } catch (\Exception $e) {
                $this->warn("Index {$indexName} does not exist or could not be deleted");
            }
        }

        // Get mapping for this index from config
        $mapping = $this->getMapping($indexName);

        if (!$mapping) {
            $this->error("No mapping found for index: {$indexName}");
            $this->newLine();
            $this->info('Available mappings:');
            foreach (array_keys(config('elasticsearch.mappings', [])) as $template) {
                $this->line("  • {$template}");
            }
            $this->newLine();
            $this->info('To add a custom mapping, edit: config/elasticsearch.php');
            $this->info('Or run: php artisan elasticsearch:setup-index --list');
            return Command::FAILURE;
        }

        // Create index with mapping
        try {
            $params = [
                'index' => $indexName,
                'body' => [
                    'settings' => config('elasticsearch.settings', [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                    ]),
                    'mappings' => [
                        'properties' => $mapping
                    ]
                ]
            ];

            $response = $client->indices()->create($params);
            
            $this->info("✓ Index '{$indexName}' created successfully!");
            $this->newLine();
            
            // Display mapping
            $this->info('Index mapping:');
            foreach ($mapping as $field => $config) {
                $type = $config['type'];
                $extra = '';
                
                if ($type === 'dense_vector') {
                    $extra = " ({$config['dims']} dims, {$config['similarity']} similarity)";
                } elseif (isset($config['analyzer'])) {
                    $extra = " (analyzer: {$config['analyzer']})";
                }
                
                $this->line("  • {$field}: {$type}{$extra}");
            }
            
            $this->newLine();
            $this->info('You can now index documents to this index.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('✗ Failed to create index');
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get mapping for the specified index from config
     */
    protected function getMapping(string $indexName): ?array
    {
        return config("elasticsearch.mappings.{$indexName}");
    }

    /**
     * List all available index mappings
     */
    protected function listAvailableMappings(): int
    {
        $mappings = config('elasticsearch.mappings', []);

        if (empty($mappings)) {
            $this->warn('No mappings found in config/elasticsearch.php');
            return Command::FAILURE;
        }

        $this->info('Available Elasticsearch Index Mappings:');
        $this->newLine();

        foreach ($mappings as $indexName => $fields) {
            $this->line("📦 {$indexName}");
            
            foreach ($fields as $fieldName => $config) {
                $type = $config['type'];
                $extra = '';
                
                if ($type === 'dense_vector') {
                    $dims = $config['dims'] ?? 'unknown';
                    $similarity = $config['similarity'] ?? 'unknown';
                    $extra = " ({$dims} dims, {$similarity})";
                } elseif (isset($config['analyzer'])) {
                    $extra = " (analyzer: {$config['analyzer']})";
                }
                
                $this->line("  • {$fieldName}: {$type}{$extra}");
            }
            
            $this->newLine();
        }

        $this->info('To create an index, run:');
        $this->line('  php artisan elasticsearch:setup-index {index-name}');
        $this->newLine();
        $this->info('To add custom mappings, edit: config/elasticsearch.php');

        return Command::SUCCESS;
    }
}
