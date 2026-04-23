<?php

namespace App\Http\Controllers\Api;

use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
use App\Application\Audit\Services\AuthenticatedAuditActorResolver;
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
    public function __construct(
        private readonly AuditEventWriter $audit,
        private readonly AuthenticatedAuditActorResolver $actors,
    ) {}

    public function store(IngestDocumentRequest $request, IngestDocumentAction $action): JsonResponse
    {
        $document = $action->execute($request->validated());

        $this->audit->write(new AuditEventData(
            eventName: 'document.ingested',
            subject: new AuditSubjectData('document', $document->id),
            actor: $this->actors->resolve($request->user()),
            source: 'api.document_ingestion',
            metadata: [
                'title' => $document->title,
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'storage_disk' => $document->storage_disk,
            ],
        ));

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function extractKnowledge(Document $document, ExtractDocumentKnowledgeItemsService $service): AnonymousResourceCollection
    {
        $items = $service->extract($document);

        $this->audit->write(new AuditEventData(
            eventName: 'document.knowledge_extracted',
            subject: new AuditSubjectData('document', $document->id),
            actor: $this->actors->resolve(request()->user()),
            source: 'api.knowledge_extraction',
            metadata: [
                'knowledge_item_count' => $items->count(),
            ],
        ));

        return KnowledgeItemResource::collection($items);
    }
}
