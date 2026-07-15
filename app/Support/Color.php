<?php

namespace App\Support;

/**
 * Turns a stored hex colour (e.g. "#000000") into a human-readable name so the
 * raw hex is never shown to customers. Exact catalogue values are mapped
 * directly; anything else falls back to the nearest basic colour by RGB.
 */
class Color
{
    /** Exact matches for values used across the catalogue. */
    protected static array $named = [
        '#000000' => 'Black',
        '#ffffff' => 'White',
        '#e02020' => 'Red',
        '#6b7280' => 'Gray',
        '#808080' => 'Gray',
        '#1a1a2e' => 'Navy',
        '#0f1035' => 'Navy',
        '#c0c0c0' => 'Silver',
        '#f5f5dc' => 'Beige',
    ];

    /** Reference points for the nearest-colour fallback. */
    protected static array $basic = [
        'Black'  => [0, 0, 0],
        'White'  => [255, 255, 255],
        'Gray'   => [128, 128, 128],
        'Red'    => [220, 32, 32],
        'Orange' => [255, 140, 0],
        'Yellow' => [240, 220, 40],
        'Green'  => [40, 160, 60],
        'Blue'   => [40, 90, 200],
        'Navy'   => [20, 20, 60],
        'Purple' => [128, 64, 160],
        'Pink'   => [240, 150, 180],
        'Brown'  => [140, 80, 40],
        'Beige'  => [220, 205, 170],
    ];

    public static function name(?string $hex): ?string
    {
        if (! $hex) {
            return null;
        }

        $h = strtolower(trim($hex));
        if ($h === '') {
            return null;
        }
        if ($h[0] !== '#') {
            $h = '#' . $h;
        }
        // Expand shorthand (#abc -> #aabbcc)
        if (strlen($h) === 4) {
            $h = '#' . $h[1] . $h[1] . $h[2] . $h[2] . $h[3] . $h[3];
        }

        if (isset(static::$named[$h])) {
            return static::$named[$h];
        }

        $rgb = static::toRgb($h);
        if (! $rgb) {
            // If it isn't a colour we can parse, don't leak the raw value.
            return 'Custom';
        }

        $best = 'Custom';
        $bestDist = PHP_INT_MAX;
        foreach (static::$basic as $name => $ref) {
            $d = ($rgb[0] - $ref[0]) ** 2 + ($rgb[1] - $ref[1]) ** 2 + ($rgb[2] - $ref[2]) ** 2;
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $name;
            }
        }

        return $best;
    }

    protected static function toRgb(string $h): ?array
    {
        if (! preg_match('/^#([0-9a-f]{6})$/', $h, $m)) {
            return null;
        }
        $int = hexdec($m[1]);

        return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
    }
}
