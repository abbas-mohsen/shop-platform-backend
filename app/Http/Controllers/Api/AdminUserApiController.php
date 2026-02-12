<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserApiController extends Controller
{
    /**
     * GET /api/admin/users
     * List all users (paginated). Only super_admin can access.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $perPage = min((int) $request->query('per_page', 20), 100);

        $users = User::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    /**
     * PUT /api/admin/users/{user}/role
     * Change a user's role. Only super_admin can do this.
     *
     * Body: { "role": "customer" | "admin" | "super_admin" }
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $currentUser = $request->user();

        // Cannot change your own role
        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot change your own role.',
            ], 422);
        }

        $data = $request->validate([
            'role' => ['required', 'in:' . implode(',', User::ROLES)],
        ]);

        $user->update([
            'role'     => $data['role'],
            'is_admin' => in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]),
        ]);

        return response()->json([
            'message' => "Role updated to {$data['role']}.",
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Ensure only super_admin can access these endpoints.
     */
    private function authorizeSuperAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can manage users.');
        }
    }
}
