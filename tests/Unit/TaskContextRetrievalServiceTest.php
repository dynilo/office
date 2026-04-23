<?php

use App\Application\Context\Formatters\ContextBlockFormatter;
use App\Application\Context\Services\TaskContextRetrievalService;
use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Memory\Data\EmbeddingData;
use App\Application\Memory\Data\SimilarityMatchData;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

it('selects top-k context blocks deterministically and formats them for prompts', function (): void {
    $task = new Task([
        'title' => 'Prepare market summary',
        'summary' => 'Need supporting context',
        'description' => 'Focus on enterprise software signals.',
        'requested_agent_role' => 'research',
        'payload' => [
            'region' => 'us',
            'topic' => 'software',
        ],
    ]);

    $document = new Document([
        'title' => 'Enterprise Market Notes',
    ]);

    $first = (new KnowledgeItem)->forceFill([
        'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        'document_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAA',
        'title' => 'Market Note Chunk 1',
        'content' => 'Enterprise buyers remain cautious.',
        'metadata' => [
            'document_title' => $document->title,
            'chunk_index' => 0,
        ],
    ]);
    $second = (new KnowledgeItem)->forceFill([
        'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAW',
        'document_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAA',
        'title' => 'Market Note Chunk 2',
        'content' => 'Budget approvals are slowing in Q4.',
        'metadata' => [
            'document_title' => $document->title,
            'chunk_index' => 1,
        ],
    ]);
    $third = (new KnowledgeItem)->forceFill([
        'id' => '01ARZ3NDEKTSV4RRFFQ69G5FAX',
        'document_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAA',
        'title' => 'Market Note Chunk 3',
        'content' => 'Security reviews are delaying deals.',
        'metadata' => [
            'document_title' => $document->title,
            'chunk_index' => 2,
        ],
    ]);

    $generator = new class implements EmbeddingGenerator
    {
        public string $lastInput = '';

        public function generate(string $input): EmbeddingData
        {
            $this->lastInput = $input;

            return new EmbeddingData(
                vector: [0.1, 0.2, 0.3],
                model: 'fake-embedding-model',
            );
        }
    };

    $search = new class($first, $second, $third) implements KnowledgeSimilaritySearch
    {
        public function __construct(
            private readonly KnowledgeItem $first,
            private readonly KnowledgeItem $second,
            private readonly KnowledgeItem $third,
        ) {}

        public function search(array $embedding, int $limit = 5): array
        {
            return [
                new SimilarityMatchData($this->third, 0.33),
                new SimilarityMatchData($this->first, 0.10),
                new SimilarityMatchData($this->second, 0.10),
                new SimilarityMatchData($this->first, 0.10),
            ];
        }
    };

    $service = new TaskContextRetrievalService($generator, $search, new ContextBlockFormatter);

    $blocks = $service->retrieve($task, 2);

    expect($generator->lastInput)->toContain('Task title: Prepare market summary')
        ->and($generator->lastInput)->toContain('Requested agent role: research')
        ->and($generator->lastInput)->toContain('"region":"us"')
        ->and($blocks)->toHaveCount(2)
        ->and($blocks[0]->knowledgeItem->id)->toBe($first->id)
        ->and($blocks[1]->knowledgeItem->id)->toBe($second->id)
        ->and($blocks[0]->formattedBlock)->toContain('[Retrieved Context]')
        ->and($blocks[0]->formattedBlock)->toContain('Title: Market Note Chunk 1')
        ->and($blocks[0]->formattedBlock)->toContain('Document: Enterprise Market Notes')
        ->and($blocks[0]->formattedBlock)->toContain('Chunk: 1')
        ->and($blocks[0]->formattedBlock)->toContain('Relevance: 0.900000')
        ->and($blocks[0]->relevanceScore)->toBe(0.9)
        ->and($blocks[0]->formattedBlock)->toContain('Content:')
        ->and($service->retrieveFormattedBlocks($task, 1))->toHaveCount(1);
});

it('filters retrieval candidates by max distance and exposes deterministic diagnostics', function (): void {
    config()->set('context.retrieval.max_distance', 0.2);

    $task = new Task([
        'title' => 'Prepare risk summary',
        'payload' => ['topic' => 'risk'],
    ]);

    $first = (new KnowledgeItem)->forceFill([
        'id' => '01BRZ3NDEKTSV4RRFFQ69G5FAA',
        'title' => 'Risk Chunk 1',
        'content' => 'A relevant risk note.',
        'metadata' => [],
    ]);
    $second = (new KnowledgeItem)->forceFill([
        'id' => '01BRZ3NDEKTSV4RRFFQ69G5FAB',
        'title' => 'Risk Chunk 2',
        'content' => 'A distant risk note.',
        'metadata' => [],
    ]);
    $third = (new KnowledgeItem)->forceFill([
        'id' => '01BRZ3NDEKTSV4RRFFQ69G5FAC',
        'title' => 'Risk Chunk 3',
        'content' => 'Another relevant risk note.',
        'metadata' => [],
    ]);

    $service = new TaskContextRetrievalService(
        new class implements EmbeddingGenerator
        {
            public function generate(string $input): EmbeddingData
            {
                return new EmbeddingData([0.1, 0.2], 'fake');
            }
        },
        new class($first, $second, $third) implements KnowledgeSimilaritySearch
        {
            public function __construct(
                private readonly KnowledgeItem $first,
                private readonly KnowledgeItem $second,
                private readonly KnowledgeItem $third,
            ) {}

            public function search(array $embedding, int $limit = 5): array
            {
                return [
                    new SimilarityMatchData($this->second, 0.55),
                    new SimilarityMatchData($this->first, 0.05),
                    new SimilarityMatchData($this->third, 0.19),
                    new SimilarityMatchData($this->first, 0.05),
                ];
            }
        },
        new ContextBlockFormatter,
    );

    $result = $service->retrieveResult($task, 3);

    expect($result->blocks)->toHaveCount(2)
        ->and($result->blocks[0]->knowledgeItem->id)->toBe($first->id)
        ->and($result->blocks[1]->knowledgeItem->id)->toBe($third->id)
        ->and($result->diagnostics['candidate_count'])->toBe(4)
        ->and($result->diagnostics['selected_count'])->toBe(2)
        ->and($result->diagnostics['threshold_rejected_count'])->toBe(1)
        ->and($result->diagnostics['duplicate_count'])->toBe(1)
        ->and($result->diagnostics['selected_knowledge_item_ids'])->toBe([$first->id, $third->id])
        ->and($result->diagnostics['rejected'])->toContain([
            'knowledge_item_id' => $second->id,
            'distance' => 0.55,
            'reason' => 'above_max_distance',
        ])
        ->and($result->diagnostics['rejected'])->toContain([
            'knowledge_item_id' => $first->id,
            'distance' => 0.05,
            'reason' => 'duplicate',
        ]);
});

it('returns no context blocks when similarity search returns no matches', function (): void {
    $task = new Task([
        'title' => 'Review operations report',
        'payload' => ['report' => 'ops'],
    ]);

    $service = new TaskContextRetrievalService(
        new class implements EmbeddingGenerator
        {
            public function generate(string $input): EmbeddingData
            {
                return new EmbeddingData([0.5], 'fake');
            }
        },
        new class implements KnowledgeSimilaritySearch
        {
            public function search(array $embedding, int $limit = 5): array
            {
                return [];
            }
        },
        new ContextBlockFormatter,
    );

    expect($service->retrieve($task, 3))->toBe([])
        ->and($service->retrieveFormattedBlocks($task, 3))->toBe([])
        ->and($service->retrieveResult($task, 0)->diagnostics['requested_top_k'])->toBe(0);
});
