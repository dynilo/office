<?php

use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('extracts deterministic knowledge items from an ingested document', function (): void {
    config()->set('knowledge.chunking.max_characters', 80);

    $document = Document::factory()->create([
        'title' => 'Market Brief',
        'raw_text' => implode("\n\n", [
            'Alpha paragraph carries the first idea.',
            'Beta paragraph adds the second idea.',
            'Gamma paragraph closes the note.',
        ]),
        'checksum' => 'doc-checksum-1',
    ]);

    $response = $this->postJson("/api/documents/{$document->id}/extract-knowledge");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.document_id', $document->id)
        ->assertJsonPath('data.0.title', 'Market Brief - Chunk 1')
        ->assertJsonPath('data.0.content', "Alpha paragraph carries the first idea.\n\nBeta paragraph adds the second idea.")
        ->assertJsonPath('data.0.metadata.chunk_index', 0)
        ->assertJsonPath('data.0.metadata.start_offset', 0)
        ->assertJsonPath('data.0.metadata.document_checksum', 'doc-checksum-1')
        ->assertJsonPath('data.1.title', 'Market Brief - Chunk 2')
        ->assertJsonPath('data.1.content', 'Gamma paragraph closes the note.')
        ->assertJsonPath('data.1.metadata.chunk_index', 1);

    $items = KnowledgeItem::query()
        ->where('document_id', $document->id)
        ->orderBy('title')
        ->get();

    expect($items)->toHaveCount(2)
        ->and($items[0]->metadata['character_count'] ?? null)->toBe(strlen($items[0]->content))
        ->and($items[0]->metadata['source'] ?? null)->toBe('document_extraction_v1')
        ->and($items[1]->metadata['start_offset'] ?? null)->toBe(strpos($document->raw_text, 'Gamma paragraph closes the note.'));
});

it('replaces existing knowledge items when a document is re-extracted', function (): void {
    config()->set('knowledge.chunking.max_characters', 120);

    $document = Document::factory()->create([
        'title' => 'Ops Notes',
        'raw_text' => "One paragraph.\n\nTwo paragraph.",
    ]);

    KnowledgeItem::factory()->for($document)->create([
        'title' => 'Stale chunk',
        'content' => 'old',
        'content_hash' => hash('sha256', 'old'),
    ]);

    $this->postJson("/api/documents/{$document->id}/extract-knowledge")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $items = KnowledgeItem::query()->where('document_id', $document->id)->get();

    expect($items)->toHaveCount(1)
        ->and($items->first()?->title)->toBe('Ops Notes - Chunk 1')
        ->and($items->first()?->content)->toBe("One paragraph.\n\nTwo paragraph.");
});
