<?php

namespace App\Http\Controllers\Api;

use App\Application\Documents\Actions\IngestDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\IngestDocumentRequest;
use App\Http\Resources\DocumentResource;
use Illuminate\Http\JsonResponse;
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
}
