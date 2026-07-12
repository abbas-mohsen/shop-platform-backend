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
     * List all users (paginated). Admins and super admins can access.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $perPage = min((int) $request->query('per_page', 20), 100);

        $users = User::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    /**
     * PUT /api/admin/users/{user}/role
     * Change a user's role between customer and admin.
     *
     * The super_admin role is deliberately not manageable here:
     * it can never be granted (not even by the super admin), the
     * super admin's own role can never be changed, and nobody can
     * change their own role. There is exactly one super admin,
     * created by the seeder.
     *
     * Body: { "role": "customer" | "admin" }
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        $currentUser = $request->user();

        // Cannot change your own role
        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot change your own role.',
            ], 422);
        }

        // The super admin's role is untouchable
        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => "The super admin's role cannot be changed.",
            ], 422);
        }

        // super_admin is intentionally excluded from the allowed values
        $data = $request->validate([
            'role' => ['required', 'in:' . User::ROLE_CUSTOMER . ',' . User::ROLE_ADMIN],
        ], [
            'role.in' => 'Role must be customer or admin. The super admin role cannot be assigned.',
        ]);

        $user->update([
            'role'     => $data['role'],
            'is_admin' => $data['role'] === User::ROLE_ADMIN,
        ]);

        return response()->json([
            'message' => "Role updated to {$data['role']}.",
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Ensure only admins (or the super admin) can access these endpoints.
     */
    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403, 'Only admins can manage users.');
        }
    }
}
