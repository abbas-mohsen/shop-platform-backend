<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatApiController extends Controller
{
    /**
     * Handle chat messages from the frontend.
     *
     * POST /api/chat
     * Body: { "message": "text", "context": { path?: string, page?: string, product_id?: any } }
     */
    public function chat(Request $request)
    {
        $data = $request->validate([
            'message'            => ['required', 'string', 'max:1000'],
            'context'            => ['nullable', 'array'],
            'context.path'       => ['nullable', 'string'],
            'context.page'       => ['nullable', 'string'],
            'context.product_id' => ['nullable'],
        ]);

        $user    = Auth::user();
        $message = trim($data['message']);
        $context = $data['context'] ?? [];

        try {
            $reply = $this->generateAiReply($message, $context, $user);

            return response()->json([
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            Log::error('Chat assistant error', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'user_id' => $user ? $user->id : null,
            ]);

            return response()->json([
                'message' => 'Assistant is temporarily unavailable. Please try again later.',
            ], 500);
        }
    }

    /**
     * Call OpenAI Chat Completions API to generate a reply.
     */
    protected function generateAiReply(string $message, array $context, $user = null): string
    {
        $apiKey = config('services.openai.key');
        $model  = config('services.openai.model', 'gpt-4.1-mini');

        if (!$apiKey) {
            // Safe fallback if not configured correctly
            return "Our AI assistant is not fully configured yet, "
                . "but you can still browse products and use the filters on the Products page.";
        }

        $userName = $user ? $user->name : 'Customer';

        // Build context text (where the user is in the app)
        $pieces = [];
        if (!empty($context['path'])) {
            $pieces[] = "Current page path: {$context['path']}";
        }
        if (!empty($context['page'])) {
            $pieces[] = "Page title: {$context['page']}";
        }
        if (!empty($context['product_id'])) {
            $pieces[] = "User is looking at product ID: {$context['product_id']}";
        }

        $contextText = $pieces
            ? "\n\nContext about the user’s view inside the XTREMEFIT app:\n" . implode("\n", $pieces)
            : '';

        // System prompt: define behaviour of the assistant
        $systemPrompt = <<<PROMPT
You are the AI assistant for XTREMEFIT, a sportswear e-commerce website based in Lebanon.

Your job is to:
- Help users with product questions (sizes, fit, style suggestions).
- Help with basic store policies like delivery areas, shipping time, returns and payment methods.
- Always answer briefly and clearly (2–5 sentences).
- When you mention actions, guide them to parts of the site (Home, Products page, filters, cart, checkout).
- You DO NOT invent specific order statuses or tracking links. If asked about an order, tell them to check "My Orders" in their account for the latest status.

Be friendly, concise and professional.
PROMPT;

        // User message with some structured info
        $userContent = "Customer name: {$userName}\n"
            . "Question: {$message}"
            . $contextText;

        // Call OpenAI Chat Completions API
        // Docs: https://platform.openai.com/docs/api-reference/chat?utm_source=chatgpt.com
        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => $model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $userContent,
                    ],
                ],
                'temperature' => 0.6,
                'max_tokens'  => 350,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('Failed to contact OpenAI API');
        }

        $data = $response->json();
        $aiText = $data['choices'][0]['message']['content'] ?? null;

        if (!$aiText) {
            throw new \RuntimeException('Empty response from AI');
        }

        return trim($aiText);
    }
}
