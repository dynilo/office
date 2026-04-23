<?php

namespace App\Application\Context\Services;

use App\Application\Context\Data\RetrievedContextBlockData;
use App\Application\Context\Formatters\ContextBlockFormatter;
use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Memory\Data\SimilarityMatchData;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final class TaskContextRetrievalService
{
    public function __construct(
        private readonly EmbeddingGenerator $embeddings,
        private readonly KnowledgeSimilaritySearch $search,
        private readonly ContextBlockFormatter $formatter,
    ) {
    }

    /**
     * @return array<int, RetrievedContextBlockData>
     */
    public function retrieve(Task $task, ?int $limit = null): array
    {
        $topK = $limit ?? (int) config('context.retrieval.top_k', 3);

        if ($topK <= 0) {
            return [];
        }

        $query = $this->buildQueryText($task);
        $embedding = $this->embeddings->generate($query);
        $matches = $this->search->search($embedding->vector, $topK);

        usort($matches, static function (SimilarityMatchData $left, SimilarityMatchData $right): int {
            return [$left->distance, $left->knowledgeItem->id] <=> [$right->distance, $right->knowledgeItem->id];
        });

        $selected = [];
        $seen = [];

        foreach ($matches as $match) {
            if (isset($seen[$match->knowledgeItem->id])) {
                continue;
            }

            $seen[$match->knowledgeItem->id] = true;
            $selected[] = new RetrievedContextBlockData(
                knowledgeItem: $match->knowledgeItem,
                distance: $match->distance,
                formattedBlock: $this->formatter->format($match),
            );

            if (count($selected) >= $topK) {
                break;
            }
        }

        return $selected;
    }

    public function buildQueryText(Task $task): string
    {
        $payload = $task->payload ?? [];
        ksort($payload);
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return implode("\n", array_filter([
            'Task title: '.$task->title,
            $task->summary !== null ? 'Task summary: '.$task->summary : null,
            $task->description !== null ? 'Task description: '.$task->description : null,
            $task->requested_agent_role !== null ? 'Requested agent role: '.$task->requested_agent_role : null,
            'Task payload: '.($encodedPayload ?: '{}'),
        ]));
    }

    /**
     * @return array<int, string>
     */
    public function retrieveFormattedBlocks(Task $task, ?int $limit = null): array
    {
        return array_map(
            static fn (RetrievedContextBlockData $block): string => $block->formattedBlock,
            $this->retrieve($task, $limit),
        );
    }
}
