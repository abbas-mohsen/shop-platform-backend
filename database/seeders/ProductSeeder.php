<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // ── Categories ──────────────────────────────────────────────────────
        // Names are crafted so the ProductsPage group filter works:
        //   split by space → exact word match "men", "women", "shoes"
        // "Men Shoes" matches BOTH the Men filter AND the Footwear filter.
        // "Women Shoes" matches BOTH the Women filter AND the Footwear filter.
        $cats = [
            'Men Tops',
            'Men Bottoms',
            'Men Sets',
            'Men Shoes',
            'Women Tops',
            'Women Bottoms',
            'Women Sets',
            'Women Shoes',
            'Accessories',
        ];

        // Insert only categories that don't exist yet — never truncate
        foreach ($cats as $name) {
            if (!DB::table('categories')->where('name', $name)->exists()) {
                DB::table('categories')->insert([
                    'name'       => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Migrate old "Shoes" products to "Men Shoes" then remove the old category
        $oldShoesId = DB::table('categories')->where('name', 'Shoes')->value('id');
        if ($oldShoesId) {
            $menShoesId = DB::table('categories')->where('name', 'Men Shoes')->value('id');
            DB::table('products')->where('category_id', $oldShoesId)->update(['category_id' => $menShoesId]);
            DB::table('categories')->where('id', $oldShoesId)->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $catId = fn(string $name) => DB::table('categories')->where('name', $name)->value('id');

        // Clothing sizes match the admin form: ["S","M","L","XL","XXL"]
        $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $clothingStock = fn() => json_encode(['S' => 12, 'M' => 20, 'L' => 18, 'XL' => 10, 'XXL' => 5]);

        // EU shoe sizes split by gender
        $menShoeSizes   = ['40', '41', '42', '43', '44', '45', '46'];
        $womenShoeSizes = ['35', '36', '37', '38', '39', '40', '41'];
        $menShoeStock   = fn() => json_encode(['40' => 8, '41' => 12, '42' => 14, '43' => 10, '44' => 8, '45' => 5, '46' => 3]);
        $womenShoeStock = fn() => json_encode(['35' => 4, '36' => 6, '37' => 8, '38' => 10, '39' => 10, '40' => 8, '41' => 5]);

        $now = now();

        // ── Products ─────────────────────────────────────────────────────────
        $products = [

            // ─── Men Tops ──────────────────────────────────────────────────
            [
                'category'      => 'Men Tops',
                'name'          => 'Pro Training Tee',
                'description'   => 'Lightweight moisture-wicking tee built for intense training sessions. Flat-seam construction prevents chafing.',
                'price'         => 29.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#ffffff', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Tops',
                'name'          => 'Flex Compression Shirt',
                'description'   => 'Four-way stretch compression fit. Keeps muscles warm and supports performance throughout your workout.',
                'price'         => 44.99,
                'compare_at'    => 59.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#6b7280'],
            ],
            [
                'category'      => 'Men Tops',
                'name'          => 'Breathe Mesh Polo',
                'description'   => 'Engineered mesh fabric for maximum airflow. Perfect from the gym to casual outings.',
                'price'         => 39.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#ffffff', '#1e3a8a', '#15803d'],
            ],
            [
                'category'      => 'Men Tops',
                'name'          => 'Storm Performance Hoodie',
                'description'   => 'Heavyweight fleece hoodie with kangaroo pocket and adjustable drawstring. Ideal for cold-weather training.',
                'price'         => 69.99,
                'compare_at'    => 89.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#374151', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Tops',
                'name'          => 'Core Zip Jacket',
                'description'   => 'Slim-fit zip-up jacket in a smooth tech fabric. Wear it as a layer or standalone.',
                'price'         => 79.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#6b7280'],
            ],
            [
                'category'      => 'Men Tops',
                'name'          => 'Essential Sweatshirt',
                'description'   => 'Classic crew-neck sweatshirt in a soft cotton-blend fabric. Everyday comfort, all-season style.',
                'price'         => 49.99,
                'compare_at'    => 64.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#ffffff', '#d1d5db', '#9ca3af'],
            ],

            // ─── Men Bottoms ───────────────────────────────────────────────
            [
                'category'      => 'Men Bottoms',
                'name'          => 'Flex Joggers',
                'description'   => 'Tapered joggers with a brushed interior for a cozy feel. Zip pockets and ribbed cuffs for a clean finish.',
                'price'         => 54.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#374151', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Bottoms',
                'name'          => 'Athletic Training Shorts',
                'description'   => '7-inch inseam training short with built-in liner. Quick-dry fabric keeps you cool through every rep.',
                'price'         => 34.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#ffffff', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Bottoms',
                'name'          => 'Slim Tech Pants',
                'description'   => 'Stretch woven pants with a slim taper. Water-resistant finish for outdoor training.',
                'price'         => 74.99,
                'compare_at'    => 94.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#6b7280'],
            ],
            [
                'category'      => 'Men Bottoms',
                'name'          => 'Warm-Up Track Pants',
                'description'   => 'Side-stripe track pants in a smooth poly-blend. Elastic waistband with inner drawstring.',
                'price'         => 59.99,
                'compare_at'    => 79.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#1e3a8a'],
            ],

            // ─── Men Sets ──────────────────────────────────────────────────
            [
                'category'      => 'Men Sets',
                'name'          => 'Power Training Set',
                'description'   => 'Matching compression tee and jogger set. Moisture-wicking fabric engineered for high-intensity workouts.',
                'price'         => 89.99,
                'compare_at'    => 114.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Sets',
                'name'          => 'Street Ready Set',
                'description'   => 'Hoodie and jogger combo in a matching cotton-poly blend. Effortless style from training to the street.',
                'price'         => 99.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#374151', '#6b7280'],
            ],

            // ─── Women Tops ────────────────────────────────────────────────
            [
                'category'      => 'Women Tops',
                'name'          => 'Flow Sports Bra',
                'description'   => 'Medium-support sports bra with a racerback design and removable pads. Perfect for yoga and pilates.',
                'price'         => 34.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#f472b6', '#a78bfa'],
            ],
            [
                'category'      => 'Women Tops',
                'name'          => 'Sculpt Tank Top',
                'description'   => 'Fitted performance tank in a soft stretch fabric. Built-in shelf bra for light-support days.',
                'price'         => 29.99,
                'compare_at'    => 39.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#ffffff', '#f472b6', '#000000'],
            ],
            [
                'category'      => 'Women Tops',
                'name'          => 'Longline Training Top',
                'description'   => 'Longline cropped top with a high neckline and ventilated back panel. Great for gym-to-street looks.',
                'price'         => 39.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#d1d5db', '#a78bfa'],
            ],
            [
                'category'      => 'Women Tops',
                'name'          => 'Oversized Fleece Hoodie',
                'description'   => 'Soft oversized hoodie in a cozy fleece fabric. Dropped shoulders and a relaxed silhouette.',
                'price'         => 64.99,
                'compare_at'    => 84.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#fde68a', '#f472b6', '#d1d5db'],
            ],
            [
                'category'      => 'Women Tops',
                'name'          => 'Active Zip-Up',
                'description'   => 'Quarter-zip pullover in a lightweight stretch fabric. Thumb holes for extra warmth on morning runs.',
                'price'         => 54.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#15803d'],
            ],
            [
                'category'      => 'Women Tops',
                'name'          => 'Studio Crop Tee',
                'description'   => 'Cropped tee in a smooth modal-blend fabric. Relaxed fit, dropped hem, perfect for studio sessions.',
                'price'         => 27.99,
                'compare_at'    => 34.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#ffffff', '#f9fafb', '#fde68a'],
            ],

            // ─── Women Bottoms ─────────────────────────────────────────────
            [
                'category'      => 'Women Bottoms',
                'name'          => 'High-Rise Seamless Leggings',
                'description'   => 'Seamless high-rise leggings with a sculpting waistband. Four-way stretch and squat-proof fabric.',
                'price'         => 59.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#6b7280', '#a78bfa'],
            ],
            [
                'category'      => 'Women Bottoms',
                'name'          => 'Biker Shorts',
                'description'   => 'High-waisted biker shorts in a buttery-soft compression fabric. A wardrobe essential.',
                'price'         => 34.99,
                'compare_at'    => 44.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#f472b6', '#1e3a8a'],
            ],
            [
                'category'      => 'Women Bottoms',
                'name'          => 'Flare Training Pants',
                'description'   => 'Wide-leg flared pants with a high-rise waistband. Great for yoga, pilates, or casual wear.',
                'price'         => 54.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#d1d5db'],
            ],
            [
                'category'      => 'Women Bottoms',
                'name'          => 'Fleece-Lined Joggers',
                'description'   => 'Tapered joggers with a warm fleece lining. Perfect for rest days and casual outings.',
                'price'         => 49.99,
                'compare_at'    => 64.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#374151', '#fde68a'],
            ],
            [
                'category'      => 'Women Bottoms',
                'name'          => 'Sport Mini Shorts',
                'description'   => 'Lightweight running shorts with a built-in brief. Side slits for freedom of movement.',
                'price'         => 29.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#f472b6', '#15803d'],
            ],

            // ─── Women Sets ────────────────────────────────────────────────
            [
                'category'      => 'Women Sets',
                'name'          => 'Seamless Gym Set',
                'description'   => 'Sports bra and high-rise legging set in a matching seamless fabric. Complete gym-ready look.',
                'price'         => 79.99,
                'compare_at'    => 99.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#a78bfa', '#f472b6'],
            ],
            [
                'category'      => 'Women Sets',
                'name'          => 'Lounge & Train Set',
                'description'   => 'Oversized hoodie and matching biker shorts. The perfect loungewear-meets-activewear combo.',
                'price'         => 84.99,
                'compare_at'    => null,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#fde68a', '#d1d5db', '#000000'],
            ],
            [
                'category'      => 'Women Sets',
                'name'          => 'Studio Flow Set',
                'description'   => 'Cropped zip jacket and high-rise legging set for a polished studio-ready look.',
                'price'         => 89.99,
                'compare_at'    => 114.99,
                'sizes'         => $clothingSizes,
                'sizes_stock'   => $clothingStock(),
                'color_options' => ['#000000', '#15803d'],
            ],

            // ─── Men Shoes ─────────────────────────────────────────────────
            [
                'category'      => 'Men Shoes',
                'name'          => 'X1 Training Sneaker',
                'description'   => 'Versatile cross-training sneaker with a cushioned midsole and breathable mesh upper. Built for the gym floor.',
                'price'         => 89.99,
                'compare_at'    => null,
                'sizes'         => $menShoeSizes,
                'sizes_stock'   => $menShoeStock(),
                'color_options' => ['#000000', '#ffffff', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Shoes',
                'name'          => 'Speed Run Shoe',
                'description'   => 'Lightweight running shoe with a responsive foam sole and knit upper. Zero-drag design for max speed.',
                'price'         => 109.99,
                'compare_at'    => 134.99,
                'sizes'         => $menShoeSizes,
                'sizes_stock'   => $menShoeStock(),
                'color_options' => ['#000000', '#dc2626', '#1e3a8a'],
            ],
            [
                'category'      => 'Men Shoes',
                'name'          => 'Power Lift Shoe',
                'description'   => 'Flat-soled lifting shoe with a solid heel and wide toe box. Engineered for stability under heavy load.',
                'price'         => 99.99,
                'compare_at'    => 124.99,
                'sizes'         => $menShoeSizes,
                'sizes_stock'   => $menShoeStock(),
                'color_options' => ['#000000', '#374151'],
            ],
            [
                'category'      => 'Men Shoes',
                'name'          => 'Trail Runner Pro',
                'description'   => 'Rugged trail running shoe with a grippy outsole and rock-plate protection. Conquer any terrain.',
                'price'         => 119.99,
                'compare_at'    => 149.99,
                'sizes'         => $menShoeSizes,
                'sizes_stock'   => $menShoeStock(),
                'color_options' => ['#15803d', '#000000', '#92400e'],
            ],

            // ─── Women Shoes ───────────────────────────────────────────────
            [
                'category'      => 'Women Shoes',
                'name'          => 'Urban Street Sneaker',
                'description'   => 'Classic low-top sneaker with a clean silhouette. Comfortable enough for all-day wear.',
                'price'         => 74.99,
                'compare_at'    => null,
                'sizes'         => $womenShoeSizes,
                'sizes_stock'   => $womenShoeStock(),
                'color_options' => ['#ffffff', '#000000', '#d1d5db'],
            ],
            [
                'category'      => 'Women Shoes',
                'name'          => 'Slide Recovery Sandal',
                'description'   => 'Contoured EVA sole with adjustable strap. The go-to recovery slide after a tough session.',
                'price'         => 39.99,
                'compare_at'    => null,
                'sizes'         => $womenShoeSizes,
                'sizes_stock'   => $womenShoeStock(),
                'color_options' => ['#000000', '#ffffff', '#f472b6'],
            ],

            // ─── Accessories ───────────────────────────────────────────────
            [
                'category'      => 'Accessories',
                'name'          => 'Training Gloves',
                'description'   => 'Padded palm gloves with wrist support strap. Protect your hands without losing grip.',
                'price'         => 24.99,
                'compare_at'    => null,
                'sizes'         => ['S', 'M', 'L', 'XL'],
                'sizes_stock'   => json_encode(['S' => 12, 'M' => 20, 'L' => 15, 'XL' => 8]),
                'color_options' => ['#000000', '#374151'],
            ],
            [
                'category'      => 'Accessories',
                'name'          => 'Performance Headband',
                'description'   => 'Wide moisture-wicking headband that stays in place during intense workouts.',
                'price'         => 12.99,
                'compare_at'    => null,
                'sizes'         => [],
                'sizes_stock'   => json_encode([]),
                'color_options' => ['#000000', '#ffffff', '#f472b6', '#1e3a8a'],
            ],
            [
                'category'      => 'Accessories',
                'name'          => 'Sport Gym Bag',
                'description'   => '40L duffel bag with separate wet pocket, shoe compartment, and padded shoulder strap.',
                'price'         => 49.99,
                'compare_at'    => 64.99,
                'sizes'         => [],
                'sizes_stock'   => json_encode([]),
                'color_options' => ['#000000', '#1e3a8a'],
            ],
            [
                'category'      => 'Accessories',
                'name'          => 'Resistance Bands Set',
                'description'   => 'Set of 5 latex resistance bands (10-50 lbs). Ideal for home workouts, stretching, and rehab.',
                'price'         => 19.99,
                'compare_at'    => 29.99,
                'sizes'         => [],
                'sizes_stock'   => json_encode([]),
                'color_options' => [],
            ],
            [
                'category'      => 'Accessories',
                'name'          => 'Compression Knee Sleeve',
                'description'   => 'Neoprene knee sleeve providing warmth and support during squats and heavy leg days.',
                'price'         => 22.99,
                'compare_at'    => null,
                'sizes'         => ['S', 'M', 'L', 'XL'],
                'sizes_stock'   => json_encode(['S' => 10, 'M' => 18, 'L' => 14, 'XL' => 6]),
                'color_options' => ['#000000'],
            ],
            [
                'category'      => 'Accessories',
                'name'          => 'XTREMEFIT Sport Cap',
                'description'   => 'Structured six-panel cap with moisture-wicking sweatband and adjustable snap closure.',
                'price'         => 19.99,
                'compare_at'    => null,
                'sizes'         => [],
                'sizes_stock'   => json_encode([]),
                'color_options' => ['#000000', '#ffffff', '#1e3a8a'],
            ],
        ];

        foreach ($products as $p) {
            $id = $catId($p['category']);

            $totalStock = 0;
            $decodedStock = json_decode($p['sizes_stock'], true);
            if (is_array($decodedStock) && count($decodedStock)) {
                $totalStock = array_sum($decodedStock);
            }

            $existing = DB::table('products')->where('name', $p['name'])->first();

            // Fields that are always safe to overwrite
            $data = [
                'category_id'      => $id,
                'description'      => $p['description'],
                'price'            => $p['price'],
                'compare_at_price' => $p['compare_at'],
                'stock'            => $totalStock,
                'sizes'            => json_encode($p['sizes']),
                'sizes_stock'      => $p['sizes_stock'],
                'color_options'    => !empty($p['color_options']) ? json_encode($p['color_options']) : null,
                'updated_at'       => $now,
            ];

            if ($existing) {
                // Update but preserve the existing image
                DB::table('products')->where('id', $existing->id)->update($data);
            } else {
                // New product — insert with null image
                DB::table('products')->insert(array_merge($data, [
                    'name'       => $p['name'],
                    'image'      => null,
                    'created_at' => $now,
                ]));
            }
        }

        $this->command->info('Seeded ' . count($products) . ' products across ' . count($cats) . ' categories.');
    }
}
