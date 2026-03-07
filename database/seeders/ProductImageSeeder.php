<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImageSeeder extends Seeder
{
    // Full https:// URLs are stored directly — the frontend uses them as-is
    // (getImageUrl() passes through any path starting with "http")
    private const Q = '?w=700&auto=format&q=82&fit=crop';

    private const IMAGES = [
        // ── Men Tops ──────────────────────────────────────────────────────
        'Pro Training Tee'         => 'https://images.unsplash.com/photo-1499290572571-a48c08140a19',
        'Flex Compression Shirt'   => 'https://images.unsplash.com/photo-1589794094880-be5a4daf3e11',
        'Breathe Mesh Polo'        => 'https://images.unsplash.com/photo-1666358777563-91164fe0034c',
        'Storm Performance Hoodie' => 'https://images.unsplash.com/photo-1600348077475-d4db860d06f7',
        'Core Zip Jacket'          => 'https://images.unsplash.com/photo-1548883354-a3fb8460973f',
        'Essential Sweatshirt'     => 'https://images.unsplash.com/photo-1710330972862-dfdce0fc821f',

        // ── Men Bottoms ───────────────────────────────────────────────────
        'Flex Joggers'             => 'https://images.unsplash.com/photo-1552903905-5e39e774e375',
        'Athletic Training Shorts' => 'https://images.unsplash.com/photo-1604818624415-ddf23016c2f2',
        'Slim Tech Pants'          => 'https://images.unsplash.com/photo-1602585198422-d795fa9bfd6f',
        'Warm-Up Track Pants'      => 'https://images.unsplash.com/photo-1572475016332-c83b5849f472',

        // ── Men Sets ──────────────────────────────────────────────────────
        'Power Training Set'       => 'https://images.unsplash.com/photo-1541534741688-6078c738800f',
        'Street Ready Set'         => 'https://images.unsplash.com/photo-1706007662540-6efa159381d3',

        // ── Women Tops ────────────────────────────────────────────────────
        'Flow Sports Bra'          => 'https://images.unsplash.com/photo-1768929096095-8f379b34278b',
        'Sculpt Tank Top'          => 'https://images.unsplash.com/photo-1637000714369-5b0dd4fc94df',
        'Longline Training Top'    => 'https://images.unsplash.com/photo-1518611012118-696072aa579a',
        'Oversized Fleece Hoodie'  => 'https://images.unsplash.com/photo-1578470507807-3fc541d5f544',
        'Active Zip-Up'            => 'https://images.unsplash.com/photo-1674695670808-0480f62cc7d7',
        'Studio Crop Tee'          => 'https://images.unsplash.com/photo-1768929096123-a346c04ff416',

        // ── Women Bottoms ─────────────────────────────────────────────────
        'High-Rise Seamless Leggings' => 'https://images.unsplash.com/photo-1768929035644-6e146b35acd8',
        'Biker Shorts'             => 'https://images.unsplash.com/photo-1663007155224-b0dec78de9f3',
        'Flare Training Pants'     => 'https://images.unsplash.com/photo-1635101003219-3c1d41f12f3f',
        'Fleece-Lined Joggers'     => 'https://images.unsplash.com/photo-1696884749820-2355bbb4c4fb',
        'Sport Mini Shorts'        => 'https://images.unsplash.com/photo-1685066970680-09c0a23aea90',

        // ── Women Sets ────────────────────────────────────────────────────
        'Seamless Gym Set'         => 'https://images.unsplash.com/photo-1768929096117-c0b04a7c8fc2',
        'Lounge & Train Set'       => 'https://images.unsplash.com/photo-1750082829724-81833e081ec9',
        'Studio Flow Set'          => 'https://images.unsplash.com/photo-1524594152303-9fd13543fe6e',

        // ── Shoes ─────────────────────────────────────────────────────────
        'X1 Training Sneaker'      => 'https://images.unsplash.com/photo-1695459468644-717c8ae17eed',
        'Speed Run Shoe'           => 'https://images.unsplash.com/photo-1709258228137-19a8c193be39',
        'Urban Street Sneaker'     => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff',
        'Power Lift Shoe'          => 'https://images.unsplash.com/photo-1741478551868-a17b1644228d',
        'Slide Recovery Sandal'    => 'https://images.unsplash.com/photo-1660632530070-1f543fd36fb2',
        'Trail Runner Pro'         => 'https://images.unsplash.com/photo-1739132268718-53d64165d29a',

        // ── Accessories ───────────────────────────────────────────────────
        'Training Gloves'          => 'https://images.unsplash.com/photo-1576678927484-cc907957088c',
        'Performance Headband'     => 'https://images.unsplash.com/photo-1515775538093-d2d95c5ee4f5',
        'Sport Gym Bag'            => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62',
        'Resistance Bands Set'     => 'https://images.unsplash.com/photo-1584827386916-b5351d3ba34b',
        'Compression Knee Sleeve'  => 'https://images.unsplash.com/photo-1606657830879-00d70555b1b6',
        'XTREMEFIT Sport Cap'      => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b',
    ];

    public function run()
    {
        $updated = 0;

        foreach (self::IMAGES as $name => $baseUrl) {
            $rows = DB::table('products')->where('name', $name)->update([
                'image'      => $baseUrl . self::Q,
                'updated_at' => now(),
            ]);
            $updated += $rows;
        }

        $this->command->info("Updated images for {$updated} products.");
    }
}
