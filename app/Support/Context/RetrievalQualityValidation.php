<?php

namespace App\Support\Context;

use App\Application\Context\Formatters\ContextBlockFormatter;
use App\Application\Context\Services\TaskContextRetrievalService;
use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Memory\Data\EmbeddingData;
use App\Application\Memory\Data\SimilarityMatchData;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final readonly class RetrievalQualityValidation
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $topK = (int) config('context.retrieval.top_k', 3);
        $maxDistance = config('context.retrieval.max_distance');
        $distanceMetric = (string) config('memory.pgvector.distance', 'cosine');

        $checks = [
            'top_k_positive' => $topK > 0,
            'top_k_reasonable' => $topK > 0 && $topK <= 20,
            'max_distance_explicit' => $maxDistance !== null,
            'max_distance_non_negative' => $maxDistance === null ? false : (float) $maxDistance >= 0.0,
            'max_distance_within_metric_range' => $this->thresholdWithinMetricRange($distanceMetric, $maxDistance),
            'deterministic_ordering' => false,
            'duplicate_rejection' => false,
            'threshold_filtering' => false,
            'empty_results_safe' => false,
        ];

        $runtime = [
            'distance_metric' => $distanceMetric,
            'top_k' => $topK,
            'max_distance' => $maxDistance,
            'deterministic_selected_ids' => [],
            'rejected' => [],
            'empty_result_selected_count' => 0,
        ];

        if (
            $checks['top_k_positive']
            && $checks['top_k_reasonable']
            && $checks['max_distance_explicit']
            && $checks['max_distance_non_negative']
            && $checks['max_distance_within_metric_range']
        ) {
            $validationResult = $this->runSyntheticValidation((float) $maxDistance, min($topK, 3));

            $runtime['deterministic_selected_ids'] = $validationResult['selected_ids'];
            $runtime['rejected'] = $validationResult['rejected'];
            $runtime['empty_result_selected_count'] = $validationResult['empty_result_selected_count'];

            $checks['deterministic_ordering'] = $validationResult['deterministic_ordering'];
            $checks['duplicate_rejection'] = $validationResult['duplicate_rejection'];
            $checks['threshold_filtering'] = $validationResult['threshold_filtering'];
            $checks['empty_results_safe'] = $validationResult['empty_results_safe'];
        }

        return [
            'environment' => (string) config('app.env'),
            'config' => [
                'top_k' => $topK,
                'max_distance' => $maxDistance,
                'distance_metric' => $distanceMetric,
            ],
            'runtime' => $runtime,
            'checks' => $checks,
            'fallback' => [
                'safe' => true,
                'retrieval_without_matches_returns_empty_context' => true,
                'missing_or_unsafe_threshold_blocks_readiness' => true,
            ],
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    private function thresholdWithinMetricRange(string $distanceMetric, mixed $maxDistance): bool
    {
        if ($maxDistance === null) {
            return false;
        }

        $threshold = (float) $maxDistance;

        return match ($distanceMetric) {
            'cosine' => $threshold <= 2.0,
            'inner_product', 'l2' => $threshold <= 1000000.0,
            default => false,
        };
    }

    /**
     * @return array{
     *   selected_ids: array<int, string>,
     *   rejected: array<int, array<string, mixed>>,
     *   empty_result_selected_count: int,
     *   deterministic_ordering: bool,
     *   duplicate_rejection: bool,
     *   threshold_filtering: bool,
     *   empty_results_safe: bool
     * }
     */
    private function runSyntheticValidation(float $maxDistance, int $limit): array
    {
        $task = new Task([
            'title' => 'Validate retrieval quality',
            'summary' => 'Synthetic validation task',
            'payload' => ['scope' => 'validation'],
        ]);

        $first = (new KnowledgeItem)->forceFill([
            'id' => '01CRZ3NDEKTSV4RRFFQ69G5FAA',
            'title' => 'Validation Chunk A',
            'content' => 'Closest context',
            'metadata' => [],
        ]);
        $second = (new KnowledgeItem)->forceFill([
            'id' => '01CRZ3NDEKTSV4RRFFQ69G5FAB',
            'title' => 'Validation Chunk B',
            'content' => 'Second closest context',
            'metadata' => [],
        ]);
        $third = (new KnowledgeItem)->forceFill([
            'id' => '01CRZ3NDEKTSV4RRFFQ69G5FAC',
            'title' => 'Validation Chunk C',
            'content' => 'Distant context',
            'metadata' => [],
        ]);

        $service = new TaskContextRetrievalService(
            new class implements EmbeddingGenerator
            {
                public function generate(string $input): EmbeddingData
                {
                    return new EmbeddingData([0.1, 0.2, 0.3], 'validation-embedding-model');
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
                        new SimilarityMatchData($this->third, 0.95),
                        new SimilarityMatchData($this->second, 0.10),
                        new SimilarityMatchData($this->first, 0.10),
                        new SimilarityMatchData($this->first, 0.10),
                    ];
                }
            },
            new ContextBlockFormatter,
        );

        config()->set('context.retrieval.max_distance', $maxDistance);
        $result = $service->retrieveResult($task, $limit);

        $emptyResult = (new TaskContextRetrievalService(
            new class implements EmbeddingGenerator
            {
                public function generate(string $input): EmbeddingData
                {
                    return new EmbeddingData([0.1], 'validation-embedding-model');
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
        ))->retrieve($task, $limit);

        $selectedIds = $result->diagnostics['selected_knowledge_item_ids'] ?? [];
        $rejected = $result->diagnostics['rejected'] ?? [];

        return [
            'selected_ids' => $selectedIds,
            'rejected' => $rejected,
            'empty_result_selected_count' => count($emptyResult),
            'deterministic_ordering' => $selectedIds === [$first->id, $second->id],
            'duplicate_rejection' => in_array([
                'knowledge_item_id' => $first->id,
                'distance' => 0.1,
                'reason' => 'duplicate',
            ], $rejected, true),
            'threshold_filtering' => in_array([
                'knowledge_item_id' => $third->id,
                'distance' => 0.95,
                'reason' => 'above_max_distance',
            ], $rejected, true),
            'empty_results_safe' => $emptyResult === [],
        ];
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
