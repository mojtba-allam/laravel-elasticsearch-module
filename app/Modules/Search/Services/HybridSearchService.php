<?php

namespace App\Modules\Search\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class HybridSearchService
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
     * Perform hybrid search combining keyword (BM25) and vector similarity
     */
    public function search(
        string $query,
        int $size = 10,
        float $vectorWeight = 0.7,
        float $keywordWeight = 0.3
    ): array {
        try {
            // Generate embedding for the query
            $queryVector = $this->embeddingService->embed($query);

            // Hybrid search with RRF (Reciprocal Rank Fusion)
            $params = [
                'index' => 'documents',
                'body' => [
                    'size' => $size,
                    'query' => [
                        'bool' => [
                            'should' => [
                                // Keyword search (BM25)
                                [
                                    'multi_match' => [
                                        'query' => $query,
                                        'fields' => ['title^2', 'content'],
                                        'type' => 'best_fields',
                                        'boost' => $keywordWeight,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'knn' => [
                        'field' => 'embedding',
                        'query_vector' => $queryVector,
                        'k' => $size,
                        'num_candidates' => $size * 10,
                        'boost' => $vectorWeight,
                    ],
                    '_source' => ['title', 'content'],
                ],
            ];

            $response = $this->client->search($params);

            return $this->formatResults($response->asArray());
        } catch (\Exception $e) {
            Log::error('Hybrid search failed', [
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
