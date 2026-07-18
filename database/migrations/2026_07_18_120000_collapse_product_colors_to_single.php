<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Products used to support multiple colours with a per-size/per-colour stock
 * matrix (colors_stock). That was simplified to ONE informational colour per
 * product: keep the first listed colour, drop the matrix. Per-size totals in
 * sizes_stock are already authoritative (they were derived as matrix sums).
 */
class CollapseProductColorsToSingle extends Migration
{
    public function up()
    {
        DB::table('products')
            ->select('id', 'color_options')
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $options = json_decode($product->color_options ?? 'null', true);

                    $first = (is_array($options) && !empty($options))
                        ? json_encode([(string) $options[0]])
                        : null;

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'color_options' => $first,
                            'colors_stock'  => null,
                        ]);
                }
            });
    }

    public function down()
    {
        // Irreversible data simplification — the discarded colours and the
        // per-colour stock split are gone. Nothing to restore.
    }
}
