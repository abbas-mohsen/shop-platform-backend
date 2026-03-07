<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatApiController extends Controller
{
    /**
     * POST /api/chat
     * Body: { "message": "...", "context": { path?, page?, product_id? } }
     */
    public function chat(Request $request)
    {
        $data = $request->validate([
            'message'              => ['required', 'string', 'max:1000'],
            'history'              => ['nullable', 'array', 'max:10'],
            'history.*.role'       => ['required', 'string', 'in:user,assistant'],
            'history.*.content'    => ['required', 'string', 'max:2000'],
            'context'              => ['nullable', 'array'],
            'context.path'         => ['nullable', 'string'],
            'context.page'         => ['nullable', 'string'],
            'context.product_id'   => ['nullable'],
        ]);

        $user    = Auth::user();
        $message = trim($data['message']);
        $history = $data['history'] ?? [];
        $context = $data['context'] ?? [];

        // Build a combined context string from recent user turns for intent detection.
        // This lets us detect budget/gender from earlier in the conversation even when
        // the current message is just a short reply like "men" or "women".
        $recentUserText = collect($history)
            ->where('role', 'user')
            ->pluck('content')
            ->implode(' ');
        $intentContext = trim($recentUserText . ' ' . $message);

        try {
            // Fetch real products from DB using full conversation context for intent
            [$suggestedProducts, $needsGenderClarification] = $this->fetchSuggestedProducts($intentContext);

            $reply = $this->generateAiReply($message, $context, $user, $suggestedProducts, $needsGenderClarification, $history);

            return response()->json([
                'reply'    => $reply,
                'products' => $suggestedProducts,
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
     * Detect if the message is product/budget related and fetch matching products.
     * Returns [products[], needsGenderClarification].
     */
    protected function fetchSuggestedProducts(string $message): array
    {
        // Only trigger on product-related intent
        $isProductQuery = (bool) preg_match(
            '/\b(product|item|outfit|wear|buy|get|need|want|show|find|looking|'
            . 'shoes?|sneakers?|footwear|boots?|pants?|shorts?|leggings?|'
            . 'shirt|tee|top|jacket|hoodie|sweatshirt|joggers?|set|'
            . 'collection|recommend|suggest|budget|cheap|affordable|under|below|price|cost)\b|\$/i',
            $message
        );

        if (!$isProductQuery) {
            return [[], false];
        }

        // ── Extract budget ──────────────────────────────────────────────────────
        $budget = null;
        if (preg_match('/\$\s*(\d+(?:\.\d+)?)/i', $message, $m)) {
            $budget = (float) $m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*\$/i', $message, $m)) {
            $budget = (float) $m[1];
        } elseif (preg_match(
            '/(?:under|less\s+than|below|budget(?:\s+of)?)\s+(\d+(?:\.\d+)?)/i',
            $message, $m
        )) {
            $budget = (float) $m[1];
        }

        // ── Gender keywords ────────────────────────────────────────────────────
        // Use word-boundary matches to avoid "men" matching inside "women"
        $genderKeywords = [];
        if (preg_match('/\b(men|male|guy|mans|mans?)\b(?!.*women)/i', $message) &&
            !preg_match('/\b(women|female|girl|ladies|woman)\b/i', $message)) {
            $genderKeywords[] = 'Men';        // matches "Men Tops", "Men Bottoms", "Men Sets"
        }
        if (preg_match('/\b(women|female|girl|ladies|woman)\b/i', $message)) {
            $genderKeywords[] = 'Women';      // matches "Women Tops", "Women Bottoms", "Women Sets"
        }

        // ── Is this an outfit request? ─────────────────────────────────────────
        $isOutfit = (bool) preg_match(
            '/\b(outfit|full set|complete look|combination|full look|whole look)\b/i',
            $message
        );

        // ── Outfit without gender → ask for clarification ──────────────────────
        if ($isOutfit && empty($genderKeywords)) {
            return [[], true];
        }

        // ── Specific category keywords ─────────────────────────────────────────
        $categoryKeywords = [];
        if (preg_match('/\b(shoes?|sneakers?|footwear|boots?)\b/i', $message)) {
            $categoryKeywords = array_merge($categoryKeywords, ['shoe', 'sneaker']);
        }
        if (preg_match('/\b(pants?|shorts?|leggings?|bottoms?|joggers?|trousers?)\b/i', $message)) {
            $categoryKeywords = array_merge($categoryKeywords, ['bottom', 'short', 'legging', 'jogger']);
        }
        if (preg_match('/\b(shirt|tee|t-shirt|top|tops|jacket|hoodie|sweatshirt)\b/i', $message)) {
            $categoryKeywords = array_merge($categoryKeywords, ['top', 'jacket', 'hoodie']);
        }

        // ── Build category filter ──────────────────────────────────────────────
        // Gender prefix filter: "Men " or "Women " at start of category name.
        // This prevents "men" from matching "Women Tops" (old bug).
        $categoryIds = [];
        if (!empty($genderKeywords) || !empty($categoryKeywords)) {
            $catQuery = Category::query();

            if (!empty($genderKeywords) && !empty($categoryKeywords)) {
                // e.g. "men tops" → categories that start with "Men" AND contain a top keyword
                $gkws = $genderKeywords;
                $ckws = $categoryKeywords;
                $catQuery->where(function ($q) use ($gkws, $ckws, $isOutfit) {
                    // Gender-prefixed clothing categories
                    $q->where(function ($inner) use ($gkws) {
                        foreach ($gkws as $g) {
                            $inner->orWhere('name', 'like', "{$g} %");
                        }
                    })->where(function ($inner) use ($ckws) {
                        foreach ($ckws as $kw) {
                            $inner->orWhere('name', 'like', "%{$kw}%");
                        }
                    });
                    // Accessories are ungendered — include for outfit requests
                    if ($isOutfit) {
                        $q->orWhere('name', 'Accessories');
                    }
                });
            } elseif (!empty($genderKeywords)) {
                // Only gender specified (e.g. "men's outfit") → all that gender's categories
                $gkws = $genderKeywords;
                $catQuery->where(function ($q) use ($gkws, $isOutfit) {
                    foreach ($gkws as $g) {
                        $q->orWhere('name', 'like', "{$g} %");
                    }
                    // Shoes always relevant for outfits
                    if ($isOutfit) {
                        $q->orWhereIn('name', ['Shoes', 'Accessories']);
                    }
                });
            } else {
                // Only category keywords, no gender
                $ckws = $categoryKeywords;
                $catQuery->where(function ($q) use ($ckws) {
                    foreach ($ckws as $kw) {
                        $q->orWhere('name', 'like', "%{$kw}%");
                    }
                });
            }

            $categoryIds = $catQuery->pluck('id')->toArray();
        }

        // ── Build product query ────────────────────────────────────────────────
        $query = Product::with('category')->orderBy('price', 'asc');

        if ($budget !== null) {
            $query->where('price', '<=', $budget);
        }

        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        if ($isOutfit) {
            // Pick one product per category for a coherent outfit
            $all = $query->limit(40)->get(['id', 'name', 'price', 'compare_at_price', 'image', 'category_id']);
            $seenCategories = [];
            $picked = [];
            foreach ($all as $p) {
                if (!in_array($p->category_id, $seenCategories)) {
                    $seenCategories[] = $p->category_id;
                    $picked[] = $p;
                }
                if (count($picked) >= 4) {
                    break;
                }
            }
            $products = collect($picked);
        } else {
            $products = $query->limit(3)->get(['id', 'name', 'price', 'compare_at_price', 'image', 'category_id']);
        }

        $result = $products->map(fn ($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'price'            => (float) $p->price,
            'compare_at_price' => $p->compare_at_price ? (float) $p->compare_at_price : null,
            'image'            => $p->image,
            'category'         => $p->category ? $p->category->name : null,
        ])->toArray();

        return [$result, false];
    }

    /**
     * Call the OpenAI Chat Completions API.
     */
    protected function generateAiReply(
        string $message,
        array $context,
        $user = null,
        array $suggestedProducts = [],
        bool $needsGenderClarification = false,
        array $history = []
    ): string {
        $apiKey = config('services.openai.key');
        $model  = config('services.openai.model', 'gpt-4.1-mini');

        if (!$apiKey) {
            return 'Our AI assistant is not fully configured yet. '
                . 'You can browse our products and use the filters on the Products page.';
        }

        $userName = $user ? $user->name : 'Customer';

        // ── Build context for the prompt ───────────────────────────────────────
        $contextParts = [];

        if (!empty($context['path'])) {
            $contextParts[] = "Current page: {$context['path']}";
        }
        if (!empty($context['product_id'])) {
            $contextParts[] = "User is viewing product ID: {$context['product_id']}";
        }

        if ($needsGenderClarification) {
            $contextParts[] = "INSTRUCTION: The user asked for an outfit but did NOT specify gender. "
                . "You MUST ask them \"Would you like a men's or women's outfit?\" before suggesting any products. "
                . "Do not assume or guess gender.";
        }

        if (!empty($suggestedProducts)) {
            $lines = [];
            $total = 0;
            foreach ($suggestedProducts as $p) {
                $cat    = $p['category'] ? " ({$p['category']})" : '';
                $lines[] = "  • {$p['name']}{$cat} — \${$p['price']}";
                $total  += $p['price'];
            }
            $contextParts[] = "Matching products from the XTREMEFIT catalog:\n" . implode("\n", $lines);
            if (count($suggestedProducts) > 1) {
                $contextParts[] = "Combined total: \$" . number_format($total, 2);
            }
        }

        $contextBlock = $contextParts
            ? "\n\n[Context]\n" . implode("\n", $contextParts)
            : '';

        // ── Strict system prompt ───────────────────────────────────────────────
        $systemPrompt = <<<'PROMPT'
You are the official AI shopping assistant for XTREMEFIT, a sportswear e-commerce store based in Lebanon.

STRICT RULES — you must follow these at all times:

1. SCOPE: You ONLY discuss topics directly related to XTREMEFIT: products, sizes, fit, styles, prices, stock, delivery, shipping, returns, payment methods, and using the website. Nothing else.

2. OFF-TOPIC REFUSAL: If the user asks about ANYTHING outside XTREMEFIT (other brands, politics, news, general knowledge, health, coding, recipes, sports teams, celebrities, etc.), respond ONLY with:
   "I'm here to help you with XTREMEFIT products and your shopping experience. Is there anything about our collection, sizes, or delivery I can help you with?"
   Do NOT provide any off-topic information even briefly.

3. IDENTITY: Never reveal you are powered by OpenAI or GPT. You are the XTREMEFIT assistant.

4. ACCURACY: Only reference real product data provided in the [Context] block. Do NOT invent product names, prices, or features.

5. PRODUCT SUGGESTIONS: When product data is provided in context, refer to products by their exact names and prices. For outfit suggestions, calculate and mention the total price.

6. ORDERS: Do not invent order statuses or tracking links. Tell the user to check "My Orders" in their account.

7. TONE: Warm, concise, and professional. Keep replies to 2–5 sentences unless listing products.

8. STORE INFO: XTREMEFIT is a Lebanese sportswear store selling men's, women's, and footwear collections.

9. GENDER — CRITICAL: NEVER assume or guess the user's gender. If the user asks for an outfit or clothing recommendation without specifying "men's" or "women's", you MUST ask them first: "Would you like a men's or women's outfit?" Do NOT suggest any products until gender is confirmed. When gender is confirmed, only suggest products from that gender's category — never mix men's and women's items in the same outfit.

10. BUDGET RESPECT: If the user specifies a budget, ensure the combined total of all suggested items does not exceed it.
PROMPT;

        $userContent = "Customer: {$userName}\nMessage: {$message}{$contextBlock}";

        // ── Build messages array with history ──────────────────────────────────
        $openAiMessages = [['role' => 'system', 'content' => $systemPrompt]];

        // Inject prior turns so the AI has full conversation context
        foreach ($history as $turn) {
            $openAiMessages[] = [
                'role'    => $turn['role'],    // 'user' or 'assistant'
                'content' => $turn['content'],
            ];
        }

        // Current user message (may include retrieved product context)
        $openAiMessages[] = ['role' => 'user', 'content' => $userContent];

        // ── Call OpenAI ────────────────────────────────────────────────────────
        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(25)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $model,
                'messages'    => $openAiMessages,
                'temperature' => 0.3,
                'max_tokens'  => 450,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Failed to contact OpenAI API');
        }

        $responseData = $response->json();
        $aiText       = $responseData['choices'][0]['message']['content'] ?? null;

        if (!$aiText) {
            throw new \RuntimeException('Empty response from AI');
        }

        return trim($aiText);
    }
}
