<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $path = 'documents/'.fake()->uuid().'.pdf';

        return [
            'title' => fake()->sentence(3),
            'mime_type' => 'application/pdf',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'checksum' => hash('sha256', $path),
            'size_bytes' => fake()->numberBetween(1024, 1024 * 1024),
            'raw_text' => fake()->paragraph(),
            'metadata' => [
                'source' => fake()->url(),
            ],
            'ingested_at' => now()->subDay(),
            'text_extracted_at' => now()->subDay(),
        ];
    }
}
