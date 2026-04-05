<?php

namespace App\Modules\Search\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $embeddingServiceUrl;

    public function __construct()
    {
        $this->embeddingServiceUrl = config('services.embedding.url', 'http://localhost:8000');
    }

    /**
     * Generate embedding vector for given text
     */
    public function embed(string $text): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->embeddingServiceUrl}/embed", [
                'text' => $text,
            ]);

            if ($response->successful()) {
                return $response->json()['embedding'];
            }

            Log::error('Embedding service error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to generate embedding');
        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100),
            ]);

            throw $e;
        }
    }

    /**
     * Generate embeddings for multiple texts in batch
     */
    public function embedBatch(array $texts): array
    {
        return array_map(fn($text) => $this->embed($text), $texts);
    }
}
