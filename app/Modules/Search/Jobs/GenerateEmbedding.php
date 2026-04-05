<?php

namespace App\Modules\Search\Jobs;

use App\Models\Document;
use App\Modules\Search\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            // Combine title and content for embedding
            $text = $this->document->title . ' ' . $this->document->content;

            // Generate embedding
            $embedding = $embeddingService->embed($text);

            // Update document with embedding
            $this->document->update([
                'embedding' => $embedding,
            ]);

            // Index to Elasticsearch manually
            $this->indexToElasticsearch();

            Log::info('Embedding generated successfully', [
                'document_id' => $this->document->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate embedding', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Index document to Elasticsearch
     */
    protected function indexToElasticsearch(): void
    {
        $client = \Elastic\Elasticsearch\ClientBuilder::create()
            ->setHosts([config('scout.elasticsearch.hosts.0', 'http://localhost:9200')])
            ->build();

        $client->index([
            'index' => 'documents',
            'id' => $this->document->id,
            'body' => [
                'title' => $this->document->title,
                'content' => $this->document->content,
                'embedding' => $this->document->embedding,
            ],
        ]);
    }
}
