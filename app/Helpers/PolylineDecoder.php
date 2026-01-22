<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Decode Google Polyline encoded strings to latitude/longitude coordinates
 * Flespi uses this format for route encoding
 */
class PolylineDecoder
{
    /**
     * Decode an encoded polyline string to array of lat/lng coordinates
     *
     * @param string|null $encoded Encoded polyline string
     * @return array Array of ['latitude' => float, 'longitude' => float]
     */
    public static function decode(?string $encoded): array
    {
        if (empty($encoded)) {
            return [];
        }

        $points = [];
        $index = 0;
        $len = strlen($encoded);
        $lat = 0;
        $lng = 0;

        while ($index < $len) {
            // Decode latitude
            $result = self::decodeValue($encoded, $index);
            $lat += $result['value'];
            $index = $result['index'];

            // Decode longitude
            $result = self::decodeValue($encoded, $index);
            $lng += $result['value'];
            $index = $result['index'];

            $points[] = [
                'latitude' => $lat / 1e5,
                'longitude' => $lng / 1e5,
            ];
        }

        return $points;
    }

    /**
     * Decode a single encoded value
     *
     * @param string $encoded The encoded string
     * @param int $index Current position in string
     * @return array ['value' => int, 'index' => int]
     */
    private static function decodeValue(string $encoded, int $index): array
    {
        $result = 0;
        $shift = 0;

        do {
            $byte = ord($encoded[$index]) - 63;
            $index++;

            $result |= ($byte & 0x1f) << $shift;
            $shift += 5;
        } while ($byte >= 0x20);

        $deltaValue = ($result & 1) ? ~($result >> 1) : ($result >> 1);

        return [
            'value' => $deltaValue,
            'index' => $index,
        ];
    }

    /**
     * Encode array of coordinates to polyline string
     *
     * @param array $points Array of ['latitude' => float, 'longitude' => float]
     * @return string Encoded polyline
     */
    public static function encode(array $points): string
    {
        $encoded = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($points as $point) {
            $lat = (int) round($point['latitude'] * 1e5);
            $lng = (int) round($point['longitude'] * 1e5);

            $encoded .= self::encodeValue($lat - $prevLat);
            $encoded .= self::encodeValue($lng - $prevLng);

            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $encoded;
    }

    /**
     * Encode a single value
     *
     * @param int $value Value to encode
     * @return string Encoded value
     */
    private static function encodeValue(int $value): string
    {
        $encoded = '';
        $value = $value < 0 ? ~($value << 1) : ($value << 1);

        while ($value >= 0x20) {
            $encoded .= chr((0x20 | ($value & 0x1f)) + 63);
            $value >>= 5;
        }

        $encoded .= chr($value + 63);

        return $encoded;
    }
}
