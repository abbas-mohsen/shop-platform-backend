<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class EmbedProducts extends Command
{
    protected $signature = 'products:embed {--force : Re-embed products that already have an embedding}';
    protected $description = 'Generate OpenAI embeddings for products (RAG retrieval index)';

    public function handle(EmbeddingService $service): int
    {
        $query = Product::with('category');
        if (!$this->option('force')) {
            $query->whereNull('embedding');
        }

        $products = $query->get();
        $this->info("Embedding {$products->count()} products...");

        foreach ($products as $product) {
            $vector = $service->embed($service->textForProduct($product));
            $product->update(['embedding' => $vector]);
            $this->line("done: {$product->name}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
