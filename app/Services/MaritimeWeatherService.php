<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class MaritimeWeatherService
{
    public function fetchLocationWeather(?float $latitude, ?float $longitude, string $locationLabel = 'Port'): array
    {
        if ($latitude === null || $longitude === null) {
            return [
                'available' => false,
                'source' => 'open-meteo',
                'location' => $locationLabel,
                'summary' => 'Koordinat lokasi belum tersedia untuk pengecekan cuaca.',
                'temperature_c' => null,
                'wind_speed_kmh' => null,
                'weather_code' => null,
                'observed_at' => null,
            ];
        }

        $lat = round($latitude, 4);
        $lng = round($longitude, 4);
        $cacheKey = "cargotrace:weather:{$lat}:{$lng}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($lat, $lng, $locationLabel) {
            $baseUrl = rtrim((string) config('services.maritime_weather.url', 'https://api.open-meteo.com/v1/forecast'), '/');
            $timeout = max(2, (int) config('services.maritime_weather.timeout', 5));

            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->get($baseUrl, [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'current' => 'temperature_2m,wind_speed_10m,weather_code',
                    ]);

                if (! $response->successful()) {
                    return [
                        'available' => false,
                        'source' => 'open-meteo',
                        'location' => $locationLabel,
                        'summary' => 'Layanan cuaca eksternal merespons error (HTTP ' . $response->status() . ').',
                        'temperature_c' => null,
                        'wind_speed_kmh' => null,
                        'weather_code' => null,
                        'observed_at' => null,
                    ];
                }

                $current = $response->json('current');
                if (! is_array($current) || ! array_key_exists('temperature_2m', $current)) {
                    return [
                        'available' => false,
                        'source' => 'open-meteo',
                        'location' => $locationLabel,
                        'summary' => 'Data cuaca eksternal kosong atau tidak lengkap.',
                        'temperature_c' => null,
                        'wind_speed_kmh' => null,
                        'weather_code' => null,
                        'observed_at' => null,
                    ];
                }

                $tempC = isset($current['temperature_2m']) ? (float) $current['temperature_2m'] : null;
                $windKmh = isset($current['wind_speed_10m']) ? (float) $current['wind_speed_10m'] : null;
                $code = isset($current['weather_code']) ? (int) $current['weather_code'] : null;
                $observedAt = $current['time'] ?? null;

                return [
                    'available' => true,
                    'source' => 'open-meteo',
                    'location' => $locationLabel,
                    'summary' => $this->describeWeatherCode($code),
                    'temperature_c' => $tempC,
                    'wind_speed_kmh' => $windKmh,
                    'weather_code' => $code,
                    'observed_at' => is_string($observedAt) ? $observedAt : null,
                ];
            } catch (Throwable) {
                return [
                    'available' => false,
                    'source' => 'open-meteo',
                    'location' => $locationLabel,
                    'summary' => 'Layanan cuaca eksternal tidak dapat dijangkau atau mengalami timeout.',
                    'temperature_c' => null,
                    'wind_speed_kmh' => null,
                    'weather_code' => null,
                    'observed_at' => null,
                ];
            }
        });
    }

    private function describeWeatherCode(?int $code): string
    {
        return match (true) {
            $code === null => 'Kondisi cuaca tidak tercatat',
            $code === 0 => 'Cerah / visibilitas baik',
            in_array($code, [1, 2, 3], true) => 'Sebagian berawan / kondisi operasional normal',
            in_array($code, [45, 48], true) => 'Berkabut / jarak pandang terbatas',
            in_array($code, [51, 53, 55, 61, 63, 65, 80, 81, 82], true) => 'Hujan / perhatikan kondisi dek & bongkar muat',
            $code >= 95 => 'Badai petir / potensi gangguan operasional laut',
            default => "Kode cuaca WMO #{$code}",
        };
    }
}