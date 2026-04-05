<?php

namespace App\Console\Commands;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class IndexAllModels extends Command
{
    protected $signature = 'elasticsearch:index-status 
                            {--list : List all indices}
                            {--delete= : Delete an index}
                            {--stats : Show cluster statistics}';
    
    protected $description = 'Manage Elasticsearch indices (list, delete, stats)';

    public function handle()
    {
        // Initialize Elasticsearch client
        $host = config('scout.elasticsearch.hosts.0', 'http://localhost:9200');
        
        try {
            $client = ClientBuilder::create()->setHosts([$host])->build();
            $client->ping();
        } catch (\Exception $e) {
            $this->error('✗ Cannot connect to Elasticsearch');
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Handle different options
        if ($this->option('list')) {
            return $this->listIndices($client);
        }

        if ($deleteIndex = $this->option('delete')) {
            return $this->deleteIndex($client, $deleteIndex);
        }

        if ($this->option('stats')) {
            return $this->showStats($client);
        }

        // Default: show all information
        $this->listIndices($client);
        $this->newLine();
        $this->showStats($client);

        return Command::SUCCESS;
    }

    /**
     * List all indices
     */
    protected function listIndices($client): int
    {
        try {
            $response = $client->cat()->indices(['format' => 'json']);
            $indices = $response->asArray();
            
            if (empty($indices)) {
                $this->warn('No indices found.');
                return Command::SUCCESS;
            }

            $this->info('Elasticsearch Indices:');
            $this->newLine();

            $headers = ['Index', 'Health', 'Status', 'Docs', 'Size'];
            $rows = [];

            foreach ($indices as $index) {
                $rows[] = [
                    $index['index'],
                    $this->colorizeHealth($index['health']),
                    $index['status'],
                    $index['docs.count'] ?? '0',
                    $index['store.size'] ?? '0b',
                ];
            }

            $this->table($headers, $rows);

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to list indices');
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Delete an index
     */
    protected function deleteIndex($client, string $indexName): int
    {
        if (!$this->confirm("Are you sure you want to delete index '{$indexName}'?", false)) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        try {
            $client->indices()->delete(['index' => $indexName]);
            $this->info("✓ Index '{$indexName}' deleted successfully!");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to delete index '{$indexName}'");
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Show cluster statistics
     */
    protected function showStats($client): int
    {
        try {
            $healthResponse = $client->cluster()->health();
            $statsResponse = $client->cluster()->stats();
            
            $health = $healthResponse->asArray();
            $stats = $statsResponse->asArray();

            $this->info('Cluster Statistics:');
            $this->newLine();

            $this->line("Cluster Name: {$health['cluster_name']}");
            $this->line("Status: " . $this->colorizeHealth($health['status']));
            $this->line("Nodes: {$health['number_of_nodes']}");
            $this->line("Data Nodes: {$health['number_of_data_nodes']}");
            $this->line("Active Shards: {$health['active_shards']}");
            $this->line("Indices: {$stats['indices']['count']}");
            $this->line("Total Documents: {$stats['indices']['docs']['count']}");
            
            $sizeInBytes = $stats['indices']['store']['size_in_bytes'];
            $sizeInMB = round($sizeInBytes / 1024 / 1024, 2);
            $this->line("Total Size: {$sizeInMB} MB");

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to get cluster stats');
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Colorize health status
     */
    protected function colorizeHealth(string $health): string
    {
        return match($health) {
            'green' => "<fg=green>{$health}</>",
            'yellow' => "<fg=yellow>{$health}</>",
            'red' => "<fg=red>{$health}</>",
            default => $health,
        };
    }
}
