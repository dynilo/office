<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeItem>
 */
class KnowledgeItemFactory extends Factory
{
    protected $model = KnowledgeItem::class;

    public function definition(): array
    {
        $content = fake()->paragraphs(2, true);

        return [
            'document_id' => Document::factory(),
            'title' => fake()->sentence(4),
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'embedding_model' => null,
            'embedding_dimensions' => null,
            'metadata' => [
                'chunk' => fake()->numberBetween(1, 20),
            ],
            'indexed_at' => now()->subHour(),
            'embedding_generated_at' => null,
        ];
    }
}
