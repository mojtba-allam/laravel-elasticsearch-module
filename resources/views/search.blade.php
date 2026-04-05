<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Search - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl" x-data="searchApp()">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Search</h1>
            <p class="text-gray-600">Powered by Panda Search</p>
        </div>

        <!-- Search Box -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form @submit.prevent="performSearch" class="space-y-4">
                <div>
                    <input 
                        type="text" 
                        x-model="query"
                        placeholder="Enter your search query..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        autofocus
                    >
                </div>
                
                <div class="flex gap-4">
                    <button 
                        type="submit"
                        :disabled="loading || !query"
                        class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
                    >
                        <span x-show="!loading">Search</span>
                        <span x-show="loading">Searching...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Searching...</p>
        </div>

        <!-- Error Message -->
        <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800" x-text="error"></p>
        </div>

        <!-- Results -->
        <div x-show="results.length > 0 && !loading" class="space-y-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Results (<span x-text="total"></span>)
                </h2>
                <span class="text-sm text-gray-500" x-text="'Search took ' + searchTime + 'ms'"></span>
            </div>

            <template x-for="result in results" :key="result.id">
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2" x-text="result.title || result.name || 'Result'"></h3>
                    <p class="text-gray-600 mb-3" x-text="result.description || result.content || 'No description available'"></p>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Score: <span x-text="result.score.toFixed(2)"></span></span>
                        <span class="text-gray-500" x-text="result.type || 'Document'"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- No Results -->
        <div x-show="searched && results.length === 0 && !loading && !error" class="text-center py-12">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No results found</h3>
            <p class="text-gray-600">Try adjusting your search query</p>
        </div>
    </div>

    <script>
        function searchApp() {
            return {
                query: '',
                results: [],
                total: 0,
                searchTime: 0,
                loading: false,
                error: null,
                searched: false,

                async performSearch() {
                    if (!this.query.trim()) return;

                    this.loading = true;
                    this.error = null;
                    this.searched = true;

                    const startTime = Date.now();

                    try {
                        const response = await fetch('/api/search', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                query: this.query,
                                limit: 20
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Search failed');
                        }

                        const data = await response.json();
                        this.results = data.results || data.data || [];
                        this.total = data.total || this.results.length;
                        this.searchTime = Date.now() - startTime;
                    } catch (err) {
                        this.error = 'An error occurred while searching. Please try again.';
                        console.error('Search error:', err);
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
