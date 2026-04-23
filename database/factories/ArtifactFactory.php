<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artifact>
 */
class ArtifactFactory extends Factory
{
    protected $model = Artifact::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'execution_id' => null,
            'kind' => 'json',
            'name' => fake()->slug(2),
            'content_text' => null,
            'content_json' => [
                'summary' => fake()->sentence(),
                'items' => [fake()->word(), fake()->word()],
            ],
            'file_metadata' => null,
            'metadata' => [
                'source' => 'factory',
            ],
        ];
    }

    public function text(): self
    {
        return $this->state(fn (): array => [
            'kind' => 'text',
            'content_text' => fake()->paragraph(),
            'content_json' => null,
            'file_metadata' => null,
        ]);
    }

    public function file(): self
    {
        return $this->state(fn (): array => [
            'kind' => 'file',
            'content_text' => null,
            'content_json' => null,
            'file_metadata' => [
                'disk' => 'local',
                'path' => 'artifacts/'.fake()->uuid().'.txt',
                'original_name' => fake()->slug().'.txt',
                'mime_type' => 'text/plain',
                'size_bytes' => fake()->numberBetween(128, 4096),
            ],
        ]);
    }
}
