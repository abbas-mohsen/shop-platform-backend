<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingApiController extends Controller
{
    /**
     * Default values returned when a setting has not been saved yet.
     */
    private const DEFAULTS = [
        // Hero images (JSON array of storage paths)
        'hero_images'            => '[]',
        // Hero images for mobile (portrait-optimized, separate from desktop)
        'hero_images_mobile'     => '[]',

        // Hero video (single storage path, or empty)
        'hero_video'        => '',
        // Hero video for mobile (portrait-optimised, separate from desktop)
        'hero_video_mobile' => '',

        // Hero text
        'hero_badge'             => 'New season · 2026',
        'hero_eyebrow'           => 'XTREMEFIT · SS 2026',
        'hero_title'             => 'Sportswear built for training and everyday movement.',
        'hero_subtitle'          => 'Fresh drops for men and women – tops, pants, sets and footwear designed to keep up with every session, on and off the court.',
        'hero_btn_text'          => 'Shop collection',
        'hero_btn_link'          => '/products',
        'hero_secondary_btn'     => 'View featured',

        // Editorial band
        'editorial_tag'          => 'New Collection',
        'editorial_headline'     => "Engineered\nfor Movement.",
        'editorial_sub'          => 'Premium sportswear for every session — from the gym floor to the streets.',
        'editorial_cta_text'     => 'Shop the drop',
        'editorial_cta_link'     => '/products',

        // Stats strip
        'stats_orders'           => '12500',
        'stats_orders_label'     => 'Orders shipped',
        'stats_products'         => '500',
        'stats_products_label'   => 'Products',
        'stats_customers'        => '8200',
        'stats_customers_label'  => 'Happy customers',

        // Trust strip
        'free_shipping_label'    => 'Free shipping over',
        'trust_1_text'           => 'Authentic gear',
        'trust_2_text'           => 'Fast dispatch',
        'trust_3_text'           => 'Easy returns',

        // Delivery
        'delivery_charge'         => '0',
        'free_shipping_enabled'   => '1',
        'free_shipping_threshold' => '100',

        // Contact info
        'contact_email'   => '',
        'contact_phone'   => '',
        'contact_address' => '',

        // About Us page content
        'about_us' => '',

        // Privacy Policy page content
        'privacy_policy' => '',
    ];

    /**
     * GET /api/settings (public)
     * Returns all settings as a flat key→value object, with defaults for any missing keys.
     */
    public function index(): JsonResponse
    {
        $rows = StoreSetting::all()->pluck('value', 'key')->toArray();

        $result = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = array_key_exists($key, $rows) ? $rows[$key] : $default;

            // Cast typed fields
            if ($key === 'free_shipping_enabled') {
                $result[$key] = (bool) $value;
            } elseif ($key === 'free_shipping_threshold') {
                $result[$key] = (int) $value;
            } elseif ($key === 'delivery_charge') {
                $result[$key] = (float) $value;
            } elseif ($key === 'hero_images' || $key === 'hero_images_mobile') {
                $result[$key] = json_decode($value ?: '[]', true) ?? [];
            } else {
                $result[$key] = $value;
            }
        }

        return response()->json($result);
    }

    /**
     * PUT /api/admin/settings (super_admin only)
     * Accepts a flat { key: value, ... } body and upserts each allowed key.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isSuperAdmin() && ! $user->isAdmin())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // All admins may update delivery/shipping fields; super admins update everything.
        $shippingKeys = ['delivery_charge', 'free_shipping_enabled', 'free_shipping_threshold'];

        $allowed = $user->isSuperAdmin()
            ? array_diff(array_keys(self::DEFAULTS), ['hero_images', 'hero_images_mobile', 'hero_video', 'hero_video_mobile'])
            : $shippingKeys;

        $data = $request->only($allowed);

        foreach ($data as $key => $value) {
            StoreSetting::setValue($key, $value);
        }

        return response()->json(['message' => 'Settings saved.']);
    }

    /**
     * POST /api/admin/settings/hero-image (super_admin only)
     * Upload a hero image and append its path to the hero_images list.
     */
    public function storeHeroImage(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('hero', 'public');

        // Append to existing list
        $current = json_decode(StoreSetting::getValue('hero_images', '[]'), true) ?? [];
        $current[] = $path;
        StoreSetting::setValue('hero_images', json_encode($current));

        return response()->json(['path' => $path], 201);
    }

    /**
     * DELETE /api/admin/settings/hero-image (super_admin only)
     * Remove an image path from hero_images and delete the file.
     */
    public function destroyHeroImage(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $pathToRemove = $request->input('path');

        $current = json_decode(StoreSetting::getValue('hero_images', '[]'), true) ?? [];
        $current = array_values(array_filter($current, fn($p) => $p !== $pathToRemove));
        StoreSetting::setValue('hero_images', json_encode($current));

        // Best-effort file deletion
        Storage::disk('public')->delete($pathToRemove);

        return response()->json(['message' => 'Image removed.']);
    }

    /**
     * POST /api/admin/settings/hero-video (super_admin only)
     * Upload a hero background video and store its path.
     */
    public function storeHeroVideo(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo', 'max:102400'],
        ]);

        // Delete old video file if one exists
        $old = StoreSetting::getValue('hero_video', '');
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('video')->store('hero', 'public');
        StoreSetting::setValue('hero_video', $path);

        return response()->json(['path' => $path], 201);
    }

    /**
     * DELETE /api/admin/settings/hero-video (super_admin only)
     * Remove the hero video and delete its file.
     */
    public function destroyHeroVideo(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $path = StoreSetting::getValue('hero_video', '');
        if ($path) {
            Storage::disk('public')->delete($path);
        }
        StoreSetting::setValue('hero_video', '');

        return response()->json(['message' => 'Video removed.']);
    }

    /**
     * POST /api/admin/settings/hero-image-mobile (super_admin only)
     * Upload a mobile hero image and append its path to hero_images_mobile.
     */
    public function storeHeroImageMobile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('hero/mobile', 'public');

        $current = json_decode(StoreSetting::getValue('hero_images_mobile', '[]'), true) ?? [];
        $current[] = $path;
        StoreSetting::setValue('hero_images_mobile', json_encode($current));

        return response()->json(['path' => $path], 201);
    }

    /**
     * DELETE /api/admin/settings/hero-image-mobile (super_admin only)
     * Remove a mobile hero image path and delete the file.
     */
    public function destroyHeroImageMobile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $pathToRemove = $request->input('path');

        $current = json_decode(StoreSetting::getValue('hero_images_mobile', '[]'), true) ?? [];
        $current = array_values(array_filter($current, fn($p) => $p !== $pathToRemove));
        StoreSetting::setValue('hero_images_mobile', json_encode($current));

        Storage::disk('public')->delete($pathToRemove);

        return response()->json(['message' => 'Image removed.']);
    }

    /**
     * POST /api/admin/settings/hero-video-mobile (super_admin only)
     * Upload a portrait hero video for the mobile app.
     */
    public function storeHeroVideoMobile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo', 'max:102400'],
        ]);

        $old = StoreSetting::getValue('hero_video_mobile', '');
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('video')->store('hero/mobile', 'public');
        StoreSetting::setValue('hero_video_mobile', $path);

        return response()->json(['path' => $path], 201);
    }

    /**
     * DELETE /api/admin/settings/hero-video-mobile (super_admin only)
     * Remove the mobile hero video and delete its file.
     */
    public function destroyHeroVideoMobile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $path = StoreSetting::getValue('hero_video_mobile', '');
        if ($path) {
            Storage::disk('public')->delete($path);
        }
        StoreSetting::setValue('hero_video_mobile', '');

        return response()->json(['message' => 'Mobile video removed.']);
    }
}
