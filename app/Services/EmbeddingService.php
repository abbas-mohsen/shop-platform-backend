<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embed(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ])->timeout(20)->post('https://api.openai.com/v1/embeddings', [
            'model' => config('services.openai.embedding_model', 'text-embedding-3-small'),
            'input' => $text,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Embedding request failed: ' . $response->body());
        }

        return $response->json('data.0.embedding');
    }

    public function textForProduct(Product $product): string
    {
        return implode(' | ', array_filter([
            $product->name,
            $product->category ? $product->category->name : null,
            $product->description,
            '$' . $product->price,
        ]));
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = $normA = $normB = 0.0;
        foreach ($a as $i => $val) {
            $dot   += $val * $b[$i];
            $normA += $val * $val;
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
