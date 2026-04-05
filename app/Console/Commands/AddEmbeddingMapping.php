<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddEmbeddingMapping extends Command
{
    protected $signature = 'elasticsearch:add-mapping 
                            {index : The index name}
                            {--fields= : Comma-separated field definitions (e.g., "title:text,price:float,category:keyword")}
                            {--with-embedding : Add embedding field automatically}';
    
    protected $description = 'Add a new Elasticsearch mapping to config/elasticsearch.php';

    public function handle()
    {
        $indexName = $this->argument('index');
        $fieldsOption = $this->option('fields');
        $withEmbedding = $this->option('with-embedding');

        $this->info("Adding Elasticsearch mapping for: {$indexName}");
        $this->newLine();

        // Parse fields or ask interactively
        $fields = [];
        
        if ($fieldsOption) {
            $fields = $this->parseFieldsOption($fieldsOption);
        } else {
            $fields = $this->askForFields();
        }

        // Add embedding field if requested
        if ($withEmbedding) {
            $fields['embedding'] = [
                'type' => 'dense_vector',
                'dims' => 384,
                'index' => true,
                'similarity' => 'cosine',
            ];
        }

        if (empty($fields)) {
            $this->error('No fields provided. Aborting.');
            return Command::FAILURE;
        }

        // Display the mapping
        $this->info('Mapping to be added:');
        $this->newLine();
        $this->line("'{$indexName}' => [");
        foreach ($fields as $fieldName => $config) {
            $this->displayField($fieldName, $config, '    ');
        }
        $this->line('],');
        $this->newLine();

        // Confirm
        if (!$this->confirm('Add this mapping to config/elasticsearch.php?', true)) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        // Add to config file
        $this->addMappingToConfig($indexName, $fields);

        $this->info("✓ Mapping added successfully!");
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Review the mapping in config/elasticsearch.php');
        $this->line("  2. Create the index: php artisan elasticsearch:setup-index {$indexName}");
        $this->line('  3. Configure your model to use Laravel Scout with this index');

        return Command::SUCCESS;
    }

    /**
     * Parse fields from command option
     */
    protected function parseFieldsOption(string $fieldsOption): array
    {
        $fields = [];
        $fieldDefs = explode(',', $fieldsOption);

        foreach ($fieldDefs as $fieldDef) {
            $parts = explode(':', trim($fieldDef));
            if (count($parts) !== 2) {
                $this->warn("Invalid field definition: {$fieldDef}. Skipping.");
                continue;
            }

            [$fieldName, $fieldType] = $parts;
            $fields[$fieldName] = $this->createFieldConfig($fieldType);
        }

        return $fields;
    }

    /**
     * Ask for fields interactively
     */
    protected function askForFields(): array
    {
        $fields = [];
        $this->info('Enter field definitions (leave field name empty to finish):');
        $this->newLine();

        while (true) {
            $fieldName = $this->ask('Field name');
            
            if (empty($fieldName)) {
                break;
            }

            $fieldType = $this->choice(
                'Field type',
                ['text', 'keyword', 'integer', 'float', 'boolean', 'date', 'dense_vector'],
                0
            );

            $fields[$fieldName] = $this->createFieldConfig($fieldType);
            
            $this->info("✓ Added: {$fieldName} ({$fieldType})");
            $this->newLine();
        }

        return $fields;
    }

    /**
     * Create field configuration based on type
     */
    protected function createFieldConfig(string $type): array
    {
        $config = ['type' => $type];

        switch ($type) {
            case 'text':
                $config['analyzer'] = 'standard';
                break;
            case 'dense_vector':
                $config['dims'] = 384;
                $config['index'] = true;
                $config['similarity'] = 'cosine';
                break;
        }

        return $config;
    }

    /**
     * Display field configuration
     */
    protected function displayField(string $fieldName, array $config, string $indent = ''): void
    {
        $this->line("{$indent}'{$fieldName}' => [");
        foreach ($config as $key => $value) {
            $valueStr = is_bool($value) ? ($value ? 'true' : 'false') : 
                       (is_string($value) ? "'{$value}'" : $value);
            $this->line("{$indent}    '{$key}' => {$valueStr},");
        }
        $this->line("{$indent}],");
    }

    /**
     * Add mapping to config file
     */
    protected function addMappingToConfig(string $indexName, array $fields): void
    {
        $configPath = config_path('elasticsearch.php');
        $content = file_get_contents($configPath);

        // Find the mappings array
        $pattern = "/'mappings'\s*=>\s*\[/";
        
        if (!preg_match($pattern, $content)) {
            $this->error('Could not find mappings array in config file.');
            return;
        }

        // Build the new mapping entry
        $mappingCode = "\n        // {$indexName} index\n";
        $mappingCode .= "        '{$indexName}' => [\n";
        
        foreach ($fields as $fieldName => $config) {
            $mappingCode .= "            '{$fieldName}' => [\n";
            foreach ($config as $key => $value) {
                $valueStr = is_bool($value) ? ($value ? 'true' : 'false') : 
                           (is_string($value) ? "'{$value}'" : $value);
                $mappingCode .= "                '{$key}' => {$valueStr},\n";
            }
            $mappingCode .= "            ],\n";
        }
        
        $mappingCode .= "        ],\n";

        // Insert after the mappings array opening
        $content = preg_replace(
            "/'mappings'\s*=>\s*\[\s*\n/",
            "'mappings' => [\n{$mappingCode}",
            $content,
            1
        );

        file_put_contents($configPath, $content);
    }
}
