<?php

namespace App\Modules\Search\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class VectorSearchService
{
    protected $client;
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        
        $host = config('scout.elasticsearch.hosts.0', 'http://localhost:9200');

        $this->client = ClientBuilder::create()
            ->setHosts([$host])
            ->build();
    }

    /**
     * Perform vector similarity search
     */
    public function search(string $query, int $k = 10, int $numCandidates = 100): array
    {
        try {
            // Generate embedding for the query
            $queryVector = $this->embeddingService->embed($query);

            // Perform kNN search
            $params = [
                'index' => 'documents',
                'body' => [
                    'knn' => [
                        'field' => 'embedding',
                        'query_vector' => $queryVector,
                        'k' => $k,
                        'num_candidates' => $numCandidates,
                    ],
                    '_source' => ['title', 'content'],
                ],
            ];

            $response = $this->client->search($params);

            return $this->formatResults($response->asArray());
        } catch (\Exception $e) {
            Log::error('Vector search failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            throw $e;
        }
    }

    /**
     * Format Elasticsearch results
     */
    protected function formatResults(array $response): array
    {
        $hits = $response['hits']['hits'] ?? [];

        return array_map(function ($hit) {
            return [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'title' => $hit['_source']['title'] ?? '',
                'content' => $hit['_source']['content'] ?? '',
            ];
        }, $hits);
    }
}
