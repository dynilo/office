<?php

namespace App\Http\Controllers\Api;

use App\Application\Documents\Actions\IngestDocumentAction;
use App\Application\Knowledge\Services\ExtractDocumentKnowledgeItemsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\IngestDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\KnowledgeItemResource;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function store(IngestDocumentRequest $request, IngestDocumentAction $action): JsonResponse
    {
        $document = $action->execute($request->validated());

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function extractKnowledge(Document $document, ExtractDocumentKnowledgeItemsService $service): AnonymousResourceCollection
    {
        return KnowledgeItemResource::collection($service->extract($document));
    }
}
