<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreSettingApiController extends Controller
{
    /**
     * Default values returned when a setting has not been saved yet.
     */
    private const DEFAULTS = [
        'hero_eyebrow'            => 'XTREMEFIT · NEW SEASON',
        'hero_title'              => 'Sportswear built for training and everyday movement.',
        'hero_subtitle'           => 'Fresh drops for men and women – tops, pants, sets and footwear designed to keep up with every session, on and off the court.',
        'hero_btn_text'           => 'Shop collection',
        'free_shipping_enabled'   => '1',
        'free_shipping_threshold' => '100',
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
        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $allowed = array_keys(self::DEFAULTS);
        $data    = $request->only($allowed);

        foreach ($data as $key => $value) {
            StoreSetting::setValue($key, $value);
        }

        return response()->json(['message' => 'Settings saved.']);
    }
}
