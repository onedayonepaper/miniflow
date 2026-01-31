<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\TemplateResource;
use App\Models\RequestTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = RequestTemplate::query()
            ->active()
            ->when($request->type, fn ($q, $type) => $q->ofType($type))
            ->orderBy('name')
            ->get();

        return $this->success(TemplateResource::collection($templates));
    }

    public function show(RequestTemplate $template): JsonResponse
    {
        if (!$template->is_active) {
            return $this->notFound('비활성화된 양식입니다.');
        }

        $template->load('creator');

        return $this->success(new TemplateResource($template));
    }
}
