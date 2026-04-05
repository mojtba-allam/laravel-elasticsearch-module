<?php

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Search\Services\HybridSearchService;
use App\Modules\Search\Services\VectorSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Perform vector search
     */
    public function vector(Request $request, VectorSearchService $vectorSearch)
    {
        $request->validate([
            'q' => 'required|string|max:500',
            'k' => 'integer|min:1|max:100',
        ]);

        $query = $request->input('q');
        $k = $request->input('k', 10);

        $results = $vectorSearch->search($query, $k);

        return response()->json([
            'query' => $query,
            'type' => 'vector',
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Perform hybrid search (keyword + vector)
     */
    public function hybrid(Request $request, HybridSearchService $hybridSearch)
    {
        $request->validate([
            'q' => 'required|string|max:500',
            'size' => 'integer|min:1|max:100',
            'vector_weight' => 'numeric|min:0|max:1',
            'keyword_weight' => 'numeric|min:0|max:1',
        ]);

        $query = $request->input('q');
        $size = $request->input('size', 10);
        $vectorWeight = $request->input('vector_weight', 0.7);
        $keywordWeight = $request->input('keyword_weight', 0.3);

        $results = $hybridSearch->search($query, $size, $vectorWeight, $keywordWeight);

        return response()->json([
            'query' => $query,
            'type' => 'hybrid',
            'weights' => [
                'vector' => $vectorWeight,
                'keyword' => $keywordWeight,
            ],
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Default search endpoint (uses hybrid search)
     */
    public function search(Request $request, HybridSearchService $hybridSearch)
    {
        return $this->hybrid($request, $hybridSearch);
    }
}
