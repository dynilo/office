<?php

$maxDistance = env('CONTEXT_RETRIEVAL_MAX_DISTANCE');

return [
    'retrieval' => [
        'top_k' => (int) env('CONTEXT_RETRIEVAL_TOP_K', 3),
        'max_distance' => $maxDistance !== null && $maxDistance !== ''
            ? (float) $maxDistance
            : null,
    ],
];
