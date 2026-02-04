<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatApiController extends Controller
{
    /**
     * Handle chat messages from the frontend.
     *
     * POST /api/chat
     * Body: { "message": "text", "context": { ...optional } }
     */
    public function chat(Request $request)
    {
        $data = $request->validate([
            'message'        => ['required', 'string', 'max:1000'],
            'context'        => ['nullable', 'array'],
            'context.path'   => ['nullable', 'string'],
            'context.page'   => ['nullable', 'string'],
            'context.product_id' => ['nullable'],
        ]);

        $user = Auth::user();
        $message = trim($data['message']);
        $lower   = mb_strtolower($message);

        // Simple rule-based "AI" for now – you can later plug real LLM here.
        $reply = $this->generateSimpleReply($lower, $message, $data['context'] ?? [], $user);

        return response()->json([
            'reply' => $reply,
        ]);
    }

    /**
     * Very simple assistant logic for now.
     * Later you can replace this with a call to a real AI API.
     */
    protected function generateSimpleReply(string $lower, string $original, array $context, $user = null): string
    {
        $name = $user ? $user->name : 'there';

        // Shipping / delivery
        if (str_contains($lower, 'delivery') || str_contains($lower, 'ship')) {
            return "Hey {$name}! We usually deliver across Lebanon within 2–4 business days. "
                . "Delivery fees depend on your area. You can add items to your cart and see the delivery details at checkout.";
        }

        // Returns / refunds
        if (str_contains($lower, 'return') || str_contains($lower, 'refund')) {
            return "If your item doesn’t fit or you’re not happy with it, you can request a return within a few days of receiving it. "
                . "Make sure the product is unworn and keep the tags. For full details, contact our support on Instagram or WhatsApp.";
        }

        // Sizes / fit
        if (str_contains($lower, 'size') || str_contains($lower, 'fit')) {
            return "Choosing size can be tricky, {$name}. As a general tip, stick to your usual sportswear size. "
                . "If you’re between sizes, we recommend going one size up for a relaxed fit. "
                . "You can also check the available sizes on each product card or details page.";
        }

        // Payment
        if (str_contains($lower, 'payment') || str_contains($lower, 'pay') || str_contains($lower, 'cod')) {
            return "Right now, we primarily support Cash on Delivery (COD). "
                . "You’ll pay when your order arrives. Online payment options can be added as a future enhancement.";
        }

        // Product search style question
        if (str_contains($lower, 'hoodie') || str_contains($lower, 't-shirt') || str_contains($lower, 'leggings')) {
            return "We’ve got some nice pieces in that category. "
                . "You can browse the Products page and use the filters on the left (category, size, price) to narrow it down. "
                . "I’ll get smarter later and suggest exact items automatically.";
        }

        // Fallback generic
        $pathInfo = '';
        if (!empty($context['path'])) {
            $pathInfo = " I see you’re currently on: {$context['path']}.";
        }

        return "Got it, {$name}!{$pathInfo} "
            . "Right now I’m a simple assistant that can help with delivery, returns, sizes and how to use the site. "
            . "You asked: \"{$original}\".";
    }
}
