<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CategoryApiController extends Controller
{
    public function index()
    {
        // Cache categories for 1 hour — they rarely change
        $categories = Cache::remember('categories:all', 3600, function () {
            return Category::all();
        });

        return CategoryResource::collection($categories);
    }

    // ── Admin: list with product counts ───────────────────────────────────────
    public function adminIndex()
    {
        $this->requireSuperAdmin();

        $categories = Category::withCount('products')->get()->sort(function ($a, $b) {
            // Men → Women → everything else, then alphabetically within each group
            $group = function (string $name): int {
                if (str_starts_with($name, 'Men '))   return 0;
                if (str_starts_with($name, 'Women ')) return 1;
                return 2;
            };
            $ga = $group($a->name);
            $gb = $group($b->name);
            return $ga !== $gb ? $ga - $gb : strcmp($a->name, $b->name);
        })->values();

        return response()->json([
            'data' => $categories->map(fn ($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'products_count' => $c->products_count,
            ]),
        ]);
    }

    // ── Admin: create a category ──────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->requireSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        $category = Category::create($data);
        Cache::forget('categories:all');

        return response()->json(['data' => [
            'id'             => $category->id,
            'name'           => $category->name,
            'products_count' => 0,
        ]], 201);
    }

    // ── Admin: delete a category ──────────────────────────────────────────────
    public function destroy(Category $category)
    {
        $this->requireSuperAdmin();

        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete a category that has products. Reassign or delete its products first.',
            ], 422);
        }

        $category->delete();
        Cache::forget('categories:all');

        return response()->json(['message' => 'Category deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function requireSuperAdmin(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            abort(403, 'Super admin access required.');
        }
    }
}
