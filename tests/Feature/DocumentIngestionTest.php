<?php

use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('ingests a document and persists extracted raw text plus metadata', function (): void {
    Storage::fake('local');
    config()->set('runtime_storage.documents.disk', 'local');
    config()->set('runtime_storage.documents.path_prefix', 'documents');

    $file = UploadedFile::fake()->createWithContent(
        'research-notes.txt',
        "Alpha finding\nBeta finding\nGamma finding"
    );

    $response = $this->post('/api/documents/ingest', [
        'file' => $file,
        'title' => 'Research Notes',
        'metadata' => [
            'source' => 'api-test',
            'category' => 'notes',
        ],
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Research Notes')
        ->assertJsonPath('data.mime_type', 'text/plain')
        ->assertJsonPath('data.raw_text', "Alpha finding\nBeta finding\nGamma finding")
        ->assertJsonPath('data.metadata.source', 'api-test')
        ->assertJsonPath('data.metadata.category', 'notes')
        ->assertJsonPath('data.metadata.original_filename', 'research-notes.txt')
        ->assertJsonPath('data.metadata.ingestion.storage_intent', 'runtime_document')
        ->assertJsonPath('data.metadata.extraction.parser', 'plain_text')
        ->assertJsonPath('data.metadata.extraction.line_count', 3);

    /** @var Document $document */
    $document = Document::query()->firstOrFail();

    expect($document->title)->toBe('Research Notes')
        ->and($document->raw_text)->toBe("Alpha finding\nBeta finding\nGamma finding")
        ->and($document->metadata['source'] ?? null)->toBe('api-test')
        ->and($document->storage_path)->toStartWith('documents/')
        ->and($document->metadata['extraction']['character_count'] ?? null)->toBe(40)
        ->and($document->ingested_at)->not->toBeNull()
        ->and($document->text_extracted_at)->not->toBeNull();

    Storage::disk('local')->assertExists($document->storage_path);
});

it('returns validation errors for unsupported document types', function (): void {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('scan.pdf', 64, 'application/pdf');

    $response = $this->post('/api/documents/ingest', [
        'file' => $file,
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);

    expect(Document::query()->count())->toBe(0);
});
