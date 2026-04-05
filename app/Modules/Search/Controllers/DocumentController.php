<?php

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Modules\Search\Jobs\GenerateEmbedding;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents
     */
    public function index()
    {
        $documents = Document::latest()->paginate(20);

        return response()->json($documents);
    }

    /**
     * Store a newly created document
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $document = Document::create($validated);

        // Dispatch job to generate embedding
        GenerateEmbedding::dispatch($document);

        return response()->json([
            'message' => 'Document created successfully',
            'document' => $document,
        ], 201);
    }

    /**
     * Display the specified document
     */
    public function show(Document $document)
    {
        return response()->json($document);
    }

    /**
     * Update the specified document
     */
    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $document->update($validated);

        // Regenerate embedding if content changed
        if (isset($validated['title']) || isset($validated['content'])) {
            GenerateEmbedding::dispatch($document);
        }

        return response()->json([
            'message' => 'Document updated successfully',
            'document' => $document,
        ]);
    }

    /**
     * Remove the specified document
     */
    public function destroy(Document $document)
    {
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }
}
