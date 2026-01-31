<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(['department'])
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(
                $request->with_deleted,
                fn ($q) => $q->withTrashed()
            )
            ->orderBy('name');

        $users = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        if ($request->per_page) {
            return $this->paginated(UserResource::collection($users));
        }

        return $this->success(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['department', 'managedDepartments']);

        return $this->success(new UserResource($user));
    }
}
