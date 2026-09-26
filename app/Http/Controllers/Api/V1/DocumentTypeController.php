<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentTypeResource;
use App\Models\DocumentType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DocumentTypeResource::collection(DocumentType::query()->orderBy('nom')->get());
    }
}
