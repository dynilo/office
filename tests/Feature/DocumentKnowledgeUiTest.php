<?php

use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders document ingestion and knowledge visibility page', function (): void {
    $document = Document::factory()->create([
        'title' => 'Research Notes',
        'mime_type' => 'text/plain',
        'storage_path' => 'documents/research-notes.txt',
        'raw_text' => "Alpha finding\nBeta finding",
        'metadata' => [
            'source' => 'ui-test',
            'category' => 'notes',
        ],
    ]);
    KnowledgeItem::factory()->for($document)->create([
        'title' => 'Research Notes - Chunk 1',
        'content' => 'Alpha finding',
        'metadata' => [
            'chunk_index' => 0,
            'character_count' => 13,
        ],
    ]);
    KnowledgeItem::factory()->for($document)->create([
        'title' => 'Research Notes - Chunk 2',
        'content' => 'Beta finding',
        'metadata' => [
            'chunk_index' => 1,
            'character_count' => 12,
        ],
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/documents');

    $response->assertOk()
        ->assertSee('Document knowledge active')
        ->assertSee('Ingest documents and inspect knowledge chunks.')
        ->assertSee('Research Notes')
        ->assertSee('documents/research-notes.txt')
        ->assertSee('Alpha finding')
        ->assertSee('Research Notes - Chunk 1')
        ->assertSee('Research Notes - Chunk 2')
        ->assertSee('Upload document')
        ->assertSee('Extract knowledge');
});

it('exposes document api integration bootstrap on the documents page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/documents');

    $response->assertOk()
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('documentKnowledge', false)
        ->assertSee('initialDocuments', false)
        ->assertSee('\/api\/documents\/ingest', false)
        ->assertSee('\/api\/documents', false)
        ->assertSee('document-upload-form')
        ->assertSee('document-search');
});

it('includes documents in stable admin navigation', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user)->get('/admin')
        ->assertOk()
        ->assertSee('/admin/documents')
        ->assertSee('Documents');
});
