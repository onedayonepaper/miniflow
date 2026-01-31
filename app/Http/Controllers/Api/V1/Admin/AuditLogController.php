<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query()
            ->with(['causer'])
            ->when($request->log_name, fn ($q, $name) => $q->inLog($name))
            ->when($request->subject_type, fn ($q, $type) => $q->where('subject_type', $type))
            ->when($request->subject_id, fn ($q, $id) => $q->where('subject_id', $id))
            ->when($request->causer_id, fn ($q, $id) => $q->where('causer_id', $id))
            ->when($request->event, fn ($q, $event) => $q->where('event', $event))
            ->when($request->from, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->where('created_at', '<=', $to))
            ->latest();

        $logs = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->limit(100)->get();

        if ($request->per_page) {
            return $this->paginated(AuditLogResource::collection($logs));
        }

        return $this->success(AuditLogResource::collection($logs));
    }

    public function show(Activity $activity): JsonResponse
    {
        $activity->load('causer');

        return $this->success(new AuditLogResource($activity));
    }
}
