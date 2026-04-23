<?php

use App\Application\Knowledge\Services\DocumentChunkingService;

it('builds deterministic paragraph-based chunks', function (): void {
    $text = implode("\n\n", [
        'Alpha paragraph carries the first idea.',
        'Beta paragraph adds the second idea.',
        'Gamma paragraph closes the note.',
    ]);

    $chunks = app(DocumentChunkingService::class)->chunk($text, 80);

    expect($chunks)->toHaveCount(2)
        ->and($chunks[0]->index)->toBe(0)
        ->and($chunks[0]->content)->toBe("Alpha paragraph carries the first idea.\n\nBeta paragraph adds the second idea.")
        ->and($chunks[0]->startOffset)->toBe(0)
        ->and($chunks[0]->endOffset)->toBe(strlen($chunks[0]->content) - 1)
        ->and($chunks[1]->index)->toBe(1)
        ->and($chunks[1]->content)->toBe('Gamma paragraph closes the note.')
        ->and($chunks[1]->startOffset)->toBe(strpos($text, 'Gamma paragraph closes the note.'))
        ->and($chunks[1]->endOffset)->toBe($chunks[1]->startOffset + strlen($chunks[1]->content) - 1);
});
