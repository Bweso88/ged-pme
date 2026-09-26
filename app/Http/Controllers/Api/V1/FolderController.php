<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FolderResource;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $parentId = $request->query('parent_id');

        $folders = Folder::query()->where('parent_id', $parentId)->orderBy('nom')->get();

        return FolderResource::collection($folders);
    }

    public function show(Folder $folder): FolderResource
    {
        Gate::authorize('view', $folder);

        return new FolderResource($folder);
    }
}
