<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Modules\Search\Services\EmbeddingService;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class SeedWithEmbeddings extends Command
{
    protected $signature = 'documents:seed-with-embeddings {--count=20 : Number of documents to create}';
    protected $description = 'Seed documents with automatic embedding generation and Elasticsearch indexing';

    protected $sampleDocuments = [
        [
            'title' => 'Laravel Tutorial - Getting Started',
            'content' => 'Laravel is a powerful PHP framework for web application development. It provides elegant syntax, robust features like routing, authentication, and database management. Laravel follows the MVC pattern and includes tools like Eloquent ORM, Blade templating, and Artisan CLI.'
        ],
        [
            'title' => 'PHP Backend Development Best Practices',
            'content' => 'Modern PHP backend development requires understanding of design patterns, security practices, and performance optimization. Use dependency injection, follow PSR standards, implement proper error handling, and leverage composer for package management. PHP 8+ offers great features like named arguments and attributes.'
        ],
        [
            'title' => 'Machine Learning Introduction',
            'content' => 'Machine learning is a subset of artificial intelligence that enables systems to learn from data. It includes supervised learning, unsupervised learning, and reinforcement learning. Common algorithms include neural networks, decision trees, and support vector machines. Python is the most popular language for ML.'
        ],
        [
            'title' => 'Elasticsearch Complete Guide',
            'content' => 'Elasticsearch is a distributed search and analytics engine built on Apache Lucene. It provides full-text search, real-time indexing, and powerful aggregations. Use it for log analysis, application search, and data analytics. Supports RESTful API and various query types including match, term, and bool queries.'
        ],
        [
            'title' => 'Docker Deployment Strategies',
            'content' => 'Docker containerization simplifies application deployment. Create Dockerfiles to define your environment, use docker-compose for multi-container applications, and implement CI/CD pipelines. Best practices include multi-stage builds, minimal base images, and proper secret management. Kubernetes orchestrates containers at scale.'
        ],
        [
            'title' => 'RESTful API Design Principles',
            'content' => 'REST APIs should follow HTTP standards, use proper status codes, and implement versioning. Design resources with clear naming conventions, support pagination and filtering, and document endpoints thoroughly. Use JSON for data exchange and implement authentication with JWT or OAuth2.'
        ],
        [
            'title' => 'Database Optimization Techniques',
            'content' => 'Optimize database performance through proper indexing, query optimization, and caching strategies. Use EXPLAIN to analyze queries, implement connection pooling, and consider read replicas for scaling. Normalize data appropriately and use database-specific features like partitioning and materialized views.'
        ],
        [
            'title' => 'Vue.js Frontend Framework',
            'content' => 'Vue.js is a progressive JavaScript framework for building user interfaces. It features reactive data binding, component-based architecture, and virtual DOM. Vue is easy to learn and integrates well with existing projects. Use Vuex for state management and Vue Router for navigation.'
        ],
        [
            'title' => 'Microservices Architecture Patterns',
            'content' => 'Microservices decompose applications into small, independent services. Each service handles a specific business capability and communicates via APIs. Benefits include scalability, technology diversity, and fault isolation. Challenges include distributed system complexity and data consistency.'
        ],
        [
            'title' => 'Python Data Science Libraries',
            'content' => 'Python offers powerful libraries for data science: NumPy for numerical computing, Pandas for data manipulation, Matplotlib and Seaborn for visualization, and Scikit-learn for machine learning. Jupyter notebooks provide interactive development environments for data analysis and experimentation.'
        ],
        [
            'title' => 'GraphQL API Development',
            'content' => 'GraphQL is a query language for APIs that allows clients to request exactly the data they need. It provides a type system, introspection, and real-time subscriptions. GraphQL reduces over-fetching and under-fetching compared to REST. Popular implementations include Apollo Server and GraphQL Yoga.'
        ],
        [
            'title' => 'Redis Caching Strategies',
            'content' => 'Redis is an in-memory data store used for caching, session management, and real-time analytics. Implement cache-aside, write-through, or write-behind patterns. Use Redis data structures like strings, hashes, lists, and sorted sets. Configure eviction policies and persistence for durability.'
        ],
        [
            'title' => 'React Hooks and State Management',
            'content' => 'React Hooks revolutionized state management in functional components. useState manages local state, useEffect handles side effects, and useContext shares data across components. Custom hooks enable reusable logic. For complex state, consider Redux, Zustand, or React Query for server state management.'
        ],
        [
            'title' => 'Node.js Performance Optimization',
            'content' => 'Optimize Node.js applications through clustering, caching, and async operations. Use PM2 for process management, implement connection pooling for databases, and leverage streams for large data processing. Profile with clinic.js and monitor with APM tools. Consider worker threads for CPU-intensive tasks.'
        ],
        [
            'title' => 'TypeScript Advanced Types',
            'content' => 'TypeScript provides powerful type system features including generics, conditional types, mapped types, and utility types. Use type guards for runtime type checking, leverage discriminated unions for type-safe state machines, and create branded types for domain modeling. Strict mode catches more errors at compile time.'
        ],
        [
            'title' => 'AWS Cloud Architecture',
            'content' => 'Design scalable cloud architectures using AWS services. Use EC2 for compute, S3 for storage, RDS for databases, and Lambda for serverless functions. Implement auto-scaling, load balancing, and multi-region deployments. Follow the Well-Architected Framework for security, reliability, and cost optimization.'
        ],
        [
            'title' => 'Git Workflow Best Practices',
            'content' => 'Effective Git workflows improve team collaboration. Use feature branches, write meaningful commit messages, and perform code reviews through pull requests. Implement Git hooks for automated testing and linting. Follow conventional commits for clear history. Rebase for clean history, merge for preserving context.'
        ],
        [
            'title' => 'MongoDB Schema Design',
            'content' => 'MongoDB schema design differs from relational databases. Embed related data for one-to-few relationships, use references for one-to-many, and implement the subset pattern for large arrays. Create compound indexes for query optimization. Use aggregation pipelines for complex data transformations and analytics.'
        ],
        [
            'title' => 'Kubernetes Container Orchestration',
            'content' => 'Kubernetes automates container deployment, scaling, and management. Define applications using Deployments, Services, and ConfigMaps. Implement rolling updates, health checks, and resource limits. Use Helm for package management and Ingress for routing. Monitor with Prometheus and visualize with Grafana.'
        ],
        [
            'title' => 'Cybersecurity Fundamentals',
            'content' => 'Implement defense-in-depth security strategies. Use HTTPS everywhere, validate and sanitize inputs, implement proper authentication and authorization. Follow OWASP Top 10 guidelines, conduct regular security audits, and keep dependencies updated. Use secrets management tools and implement least privilege access control.'
        ],
    ];

    public function handle()
    {
        $count = (int) $this->option('count');
        $embeddingService = app(EmbeddingService::class);
        
        $this->info("Starting to seed {$count} documents with embeddings...");
        $this->newLine();

        // Clear existing documents
        $this->info('Clearing existing documents...');
        Document::truncate();
        
        // Initialize Elasticsearch client
        $host = config('scout.elasticsearch.hosts.0', 'http://localhost:9200');
        $esClient = ClientBuilder::create()->setHosts([$host])->build();
        
        // Clear Elasticsearch index
        try {
            $esClient->deleteByQuery([
                'index' => 'documents',
                'body' => [
                    'query' => [
                        'match_all' => (object)[]
                    ]
                ]
            ]);
            $this->info('Cleared Elasticsearch index');
        } catch (\Exception $e) {
            $this->warn('Could not clear Elasticsearch index: ' . $e->getMessage());
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $documentsToIndex = [];
        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                // Get document data (cycle through sample documents)
                $docData = $this->sampleDocuments[$i % count($this->sampleDocuments)];
                
                // Add variation to title if we're repeating
                if ($i >= count($this->sampleDocuments)) {
                    $docData['title'] .= ' (v' . (floor($i / count($this->sampleDocuments)) + 1) . ')';
                }

                // Generate embedding
                $embedding = $embeddingService->embed($docData['content']);

                // Create document in database
                $document = Document::create([
                    'title' => $docData['title'],
                    'content' => $docData['content'],
                    'embedding' => $embedding,
                ]);

                // Prepare for bulk indexing to Elasticsearch
                $documentsToIndex[] = [
                    'index' => [
                        '_index' => 'documents',
                        '_id' => $document->id,
                    ]
                ];
                $documentsToIndex[] = [
                    'title' => $document->title,
                    'content' => $document->content,
                    'embedding' => $embedding,
                ];

                $successCount++;
                $bar->advance();

            } catch (\Exception $e) {
                $errorCount++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Bulk index to Elasticsearch
        if (!empty($documentsToIndex)) {
            $this->info('Indexing documents to Elasticsearch...');
            try {
                $response = $esClient->bulk([
                    'body' => $documentsToIndex
                ]);
                
                $responseArray = $response->asArray();
                $indexed = count($responseArray['items'] ?? []);
                $this->info("Successfully indexed {$indexed} documents to Elasticsearch");
            } catch (\Exception $e) {
                $this->error('Failed to bulk index to Elasticsearch: ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✓ Successfully created {$successCount} documents with embeddings");
        
        if ($errorCount > 0) {
            $this->warn("✗ Failed to create {$errorCount} documents");
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Documents', $successCount],
                ['Failed', $errorCount],
                ['Embedding Dimension', '384'],
                ['Model', 'sentence-transformers/all-MiniLM-L6-v2'],
            ]
        );

        return Command::SUCCESS;
    }
}
