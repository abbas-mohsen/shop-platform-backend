<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds ~45 historical orders spread over the last 30 days so the admin
 * dashboard, orders list and sales chart look like a living store.
 *
 * - Demo customers use the @demo.xtremefit email domain, so they are easy
 *   to identify (and delete) later.
 * - Orders are created directly with historical timestamps; stock is NOT
 *   touched — these represent already-fulfilled history.
 * - Idempotent: skips entirely if demo customers already exist.
 */
class DemoOrdersSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'like', '%@demo.xtremefit')->exists()) {
            $this->command->info('Demo orders already seeded — skipping.');
            return;
        }

        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->warn('No products found — run ProductSeeder first.');
            return;
        }

        $customers = collect([
            ['name' => 'Hassan Fneish',   'phone' => '+961 70 111 222'],
            ['name' => 'Maya Khalil',     'phone' => '+961 71 333 444'],
            ['name' => 'Omar Slim',       'phone' => '+961 76 555 666'],
            ['name' => 'Rima Haidar',     'phone' => '+961 78 777 888'],
            ['name' => 'Karim Nassar',    'phone' => '+961 79 999 000'],
            ['name' => 'Nour Chami',      'phone' => '+961 70 121 212'],
            ['name' => 'Ali Zein',        'phone' => '+961 71 343 434'],
            ['name' => 'Lara Mansour',    'phone' => '+961 76 565 656'],
        ])->map(function ($c) {
            $slug = strtolower(str_replace(' ', '.', $c['name']));
            return User::create([
                'name'     => $c['name'],
                'email'    => "{$slug}@demo.xtremefit",
                'phone'    => $c['phone'],
                'password' => Hash::make('password123'),
                'role'     => User::ROLE_CUSTOMER,
            ]);
        });

        $addresses = [
            'Hamra, Makdessi Street, Beirut',
            'Verdun, Rashid Karame Ave, Beirut',
            'Achrafieh, Sassine Square, Beirut',
            'Jnah, near City Mall, Beirut',
            'Saida, Riad El Solh Street',
            'Tripoli, Al Mina Road',
            'Jounieh, Fouad Chehab Highway',
            'Nabatieh, Main Square',
        ];

        // delivered-heavy mix reads like a real store; a couple of each
        // other status keeps every dashboard tile non-zero
        $statuses = array_merge(
            array_fill(0, 26, 'delivered'),
            array_fill(0, 8, 'approved'),
            array_fill(0, 5, 'pending'),
            array_fill(0, 3, 'cancelled'),
            array_fill(0, 3, 'rejected')
        );
        shuffle($statuses);

        foreach ($statuses as $i => $status) {
            // Spread across the last 30 days, busier toward the present,
            // at plausible shopping hours
            $daysAgo   = (int) floor(30 * (1 - sqrt(mt_rand(0, 1000) / 1000)));
            $createdAt = Carbon::now()
                ->subDays($daysAgo)
                ->setTime(mt_rand(9, 22), mt_rand(0, 59), mt_rand(0, 59));

            // pending orders only make sense in the recent past
            if ($status === 'pending') {
                $createdAt = Carbon::now()->subHours(mt_rand(1, 20));
            }

            $customer = $customers->random();
            $lineCount = mt_rand(1, 3);
            $total = 0;

            $order = Order::create([
                'user_id'        => $customer->id,
                'total'          => 0,
                'status'         => $status,
                'payment_method' => 'cash',
                'address'        => $addresses[array_rand($addresses)],
            ]);

            $picked = $products->random(min($lineCount, $products->count()));
            $picked = $picked instanceof Product ? collect([$picked]) : $picked;

            foreach ($picked as $product) {
                $qty   = mt_rand(1, 2);
                $price = (float) $product->price;
                $sizes = is_array($product->sizes) ? $product->sizes : [];
                $colors = is_array($product->color_options) ? $product->color_options : [];

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'size'       => $sizes ? $sizes[array_rand($sizes)] : null,
                    'color'      => $colors ? $colors[array_rand($colors)] : null,
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'line_total' => round($price * $qty, 2),
                ]);

                $total += $price * $qty;
            }

            // Bypass fillable/timestamps to set the historical dates
            $order->total = round($total, 2);
            $order->created_at = $createdAt;
            $order->updated_at = $createdAt;
            $order->save();

            OrderItem::where('order_id', $order->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command->info('✓ Seeded ' . count($statuses) . ' demo orders across the last 30 days.');
    }
}
