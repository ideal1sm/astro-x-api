<?php

namespace App\Http\Api\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GetPersonalCompilationController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
        ]);

        $birthDate = Carbon::parse($validated['birth_date']);
        $zodiac = $this->getZodiacSign($birthDate);

        $astroText = $this->getAstroText($validated['name'], $birthDate);

        $products = Product::where('zodiac_sign', $zodiac)->get();

        return response()->json([
            'code' => 'SUCCESS',
            'data' => [
                'text' => $astroText,
                'products' => $products,
            ],
            'message' => '',
            'errors' => [],
        ]);
    }

    private function getZodiacSign(Carbon $date): string
    {
        $day = $date->day;
        $month = $date->month;

        return match (true) {
            ($month == 3 && $day >= 21) || ($month == 4 && $day <= 19) => 'aries',
            ($month == 4 && $day >= 20) || ($month == 5 && $day <= 20) => 'taurus',
            ($month == 5 && $day >= 21) || ($month == 6 && $day <= 20) => 'gemini',
            ($month == 6 && $day >= 21) || ($month == 7 && $day <= 22) => 'cancer',
            ($month == 7 && $day >= 23) || ($month == 8 && $day <= 22) => 'leo',
            ($month == 8 && $day >= 23) || ($month == 9 && $day <= 22) => 'virgo',
            ($month == 9 && $day >= 23) || ($month == 10 && $day <= 22) => 'libra',
            ($month == 10 && $day >= 23) || ($month == 11 && $day <= 21) => 'scorpio',
            ($month == 11 && $day >= 22) || ($month == 12 && $day <= 21) => 'sagittarius',
            ($month == 12 && $day >= 22) || ($month == 1 && $day <= 19) => 'capricorn',
            ($month == 1 && $day >= 20) || ($month == 2 && $day <= 18) => 'aquarius',
            ($month == 2 && $day >= 19) || ($month == 3 && $day <= 20) => 'pisces',
            default => 'Неизвестно',
        };
    }

    private function getAstroText(string $name, Carbon $birthDate): string
    {
        try {
            $response = Http::get(env('ASTRO_X_TEXT_GENERATOR') . '/astro-text', [
                'name' => $name,
                'date' => $birthDate->format('d.m.Y'),
            ]);

            return $response->successful()
                ? ($response->json('content') ?? '')
                : 'Не удалось получить текст рекомендации.';

        } catch (\Throwable $e) {
            Log::error('Ошибка при получении текст рекомендации', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'Не удалось получить текст рекомендации.';
        }
    }
}
