<?php

use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Memory\Data\EmbeddingData;
use App\Application\Memory\Services\KnowledgeEmbeddingPersistenceService;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists embedding metadata safely when pgvector storage is unavailable', function (): void {
    $item = KnowledgeItem::factory()->for(Document::factory())->create();

    $persisted = app(KnowledgeEmbeddingPersistenceService::class)->persist(
        $item,
        new EmbeddingData(
            vector: [0.125, 0.25, 0.5],
            model: 'test-embedding-model',
        ),
    );

    expect($persisted->embedding_model)->toBe('test-embedding-model')
        ->and($persisted->embedding_dimensions)->toBe(3)
        ->and($persisted->embedding_generated_at)->not->toBeNull()
        ->and($persisted->metadata['memory']['vector_storage'] ?? null)->toBe('unavailable')
        ->and($persisted->metadata['memory']['embedding_dimensions'] ?? null)->toBe(3);
});

it('returns an empty similarity result set when pgvector search is unavailable', function (): void {
    KnowledgeItem::factory()->count(2)->for(Document::factory())->create([
        'embedding_model' => 'test-embedding-model',
        'embedding_dimensions' => 3,
        'embedding_generated_at' => now(),
    ]);

    $results = app(KnowledgeSimilaritySearch::class)->search([0.1, 0.2, 0.3], 3);

    expect($results)->toBeArray()->toBeEmpty();
});
