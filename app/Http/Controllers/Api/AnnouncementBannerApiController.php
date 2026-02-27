<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementBannerApiController extends Controller
{
    /**
     * GET /api/banners (public)
     * Returns only active banners, sorted by sort_order.
     */
    public function index(): JsonResponse
    {
        $banners = AnnouncementBanner::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'message', 'sort_order']);

        return response()->json($banners);
    }

    /**
     * GET /api/admin/banners (super_admin only)
     * Returns all banners (including inactive).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $banners = AnnouncementBanner::orderBy('sort_order')->orderBy('id')->get();
        return response()->json($banners);
    }

    /**
     * POST /api/admin/banners (super_admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'message'    => 'required|string|max:500',
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $banner = AnnouncementBanner::create([
            'message'    => $request->input('message'),
            'is_active'  => $request->input('is_active', true),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return response()->json($banner, 201);
    }

    /**
     * PUT /api/admin/banners/{banner} (super_admin only)
     */
    public function update(Request $request, AnnouncementBanner $banner): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'message'    => 'sometimes|string|max:500',
            'is_active'  => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $banner->update($request->only(['message', 'is_active', 'sort_order']));

        return response()->json($banner);
    }

    /**
     * DELETE /api/admin/banners/{banner} (super_admin only)
     */
    public function destroy(Request $request, AnnouncementBanner $banner): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $banner->delete();
        return response()->json(['message' => 'Banner deleted.']);
    }
}
