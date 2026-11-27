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
                'summary' => __('Location coordinates are not available for weather check.'),
                'temperature_c' => null,
                'wind_speed_kmh' => null,
                'weather_code' => null,
                'observed_at' => null,
            ];
        }

        $lat = round($latitude, 4);
        $lng = round($longitude, 4);
        $locale = app()->getLocale();
        $cacheKey = "cargotrace:weather:{$lat}:{$lng}:{$locale}";

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
                        'summary' => __('External weather service returned an error (HTTP :status).', ['status' => $response->status()]),
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
                        'summary' => __('External weather data is empty or incomplete.'),
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
                    'summary' => __('External weather service is unreachable or timed out.'),
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
            $code === null => __('Weather condition not recorded'),
            $code === 0 => __('Clear / good visibility'),
            in_array($code, [1, 2, 3], true) => __('Partly cloudy / normal operational conditions'),
            in_array($code, [45, 48], true) => __('Foggy / limited visibility'),
            in_array($code, [51, 53, 55, 61, 63, 65, 80, 81, 82], true) => __('Rain / monitor deck & cargo handling conditions'),
            $code >= 95 => __('Thunderstorm / potential maritime disruption'),
            default => __('WMO weather code #:code', ['code' => $code]),
        };
    }
}