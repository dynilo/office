<?php

namespace App\Application\Context\Services;

use App\Application\Context\Data\RetrievalResultData;
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
    ) {}

    /**
     * @return array<int, RetrievedContextBlockData>
     */
    public function retrieve(Task $task, ?int $limit = null): array
    {
        return $this->retrieveResult($task, $limit)->blocks;
    }

    public function retrieveResult(Task $task, ?int $limit = null): RetrievalResultData
    {
        $topK = $limit ?? (int) config('context.retrieval.top_k', 3);
        $maxDistance = config('context.retrieval.max_distance');

        if ($topK <= 0) {
            return new RetrievalResultData([], [
                'requested_top_k' => $topK,
                'max_distance' => $maxDistance,
                'candidate_count' => 0,
                'selected_count' => 0,
                'duplicate_count' => 0,
                'threshold_rejected_count' => 0,
                'rejected' => [],
                'selected_knowledge_item_ids' => [],
            ]);
        }

        $query = $this->buildQueryText($task);
        $embedding = $this->embeddings->generate($query);
        $matches = $this->search->search($embedding->vector, $topK);

        usort($matches, static function (SimilarityMatchData $left, SimilarityMatchData $right): int {
            return [$left->distance, $left->knowledgeItem->id] <=> [$right->distance, $right->knowledgeItem->id];
        });

        $selected = [];
        $seen = [];
        $rejected = [];
        $duplicateCount = 0;
        $thresholdRejectedCount = 0;

        foreach ($matches as $match) {
            if (isset($seen[$match->knowledgeItem->id])) {
                $duplicateCount++;
                $rejected[] = $this->rejectionDiagnostics($match, 'duplicate');

                continue;
            }

            if ($maxDistance !== null && $match->distance > (float) $maxDistance) {
                $thresholdRejectedCount++;
                $rejected[] = $this->rejectionDiagnostics($match, 'above_max_distance');

                continue;
            }

            $seen[$match->knowledgeItem->id] = true;
            $selected[] = new RetrievedContextBlockData(
                knowledgeItem: $match->knowledgeItem,
                distance: $match->distance,
                formattedBlock: $this->formatter->format($match),
                relevanceScore: $this->formatter->relevanceScore($match->distance),
            );

            if (count($selected) >= $topK) {
                break;
            }
        }

        return new RetrievalResultData($selected, [
            'requested_top_k' => $topK,
            'max_distance' => $maxDistance,
            'candidate_count' => count($matches),
            'selected_count' => count($selected),
            'duplicate_count' => $duplicateCount,
            'threshold_rejected_count' => $thresholdRejectedCount,
            'rejected' => $rejected,
            'selected_knowledge_item_ids' => array_map(
                static fn (RetrievedContextBlockData $block): string => (string) $block->knowledgeItem->id,
                $selected,
            ),
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function rejectionDiagnostics(SimilarityMatchData $match, string $reason): array
    {
        return [
            'knowledge_item_id' => (string) $match->knowledgeItem->id,
            'distance' => $match->distance,
            'reason' => $reason,
        ];
    }
}
