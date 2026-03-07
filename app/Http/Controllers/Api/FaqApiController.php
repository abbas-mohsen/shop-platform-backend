<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaqApiController extends Controller
{
    // Public: list all FAQs ordered by sort_order then id
    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->get(['id', 'question', 'answer', 'sort_order']);
        return response()->json(['data' => $faqs]);
    }

    // Admin: create
    public function store(Request $request)
    {
        $this->requireSuperAdmin();

        $data = $request->validate([
            'question'   => ['required', 'string', 'max:500'],
            'answer'     => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $faq = Faq::create([
            'question'   => $data['question'],
            'answer'     => $data['answer'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json(['data' => $faq], 201);
    }

    // Admin: update
    public function update(Request $request, Faq $faq)
    {
        $this->requireSuperAdmin();

        $data = $request->validate([
            'question'   => ['sometimes', 'required', 'string', 'max:500'],
            'answer'     => ['sometimes', 'required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $faq->update($data);
        return response()->json(['data' => $faq]);
    }

    // Admin: delete
    public function destroy(Faq $faq)
    {
        $this->requireSuperAdmin();
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted.']);
    }

    private function requireSuperAdmin(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            abort(403, 'Super admin access required.');
        }
    }
}
