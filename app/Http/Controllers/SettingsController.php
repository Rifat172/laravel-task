<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\YandexReviewsParser;

class SettingsController extends Controller
{
    public function edit()
    {
        return Inertia::render('Settings/Edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'yandex_maps_url' => [
                'nullable',
                'url',
                'max:500',
                function ($attribute, $value, $fail) {
                    if ($value && !str_contains($value, 'yandex.ru/maps')) {
                        $fail('Ссылка должна быть на Яндекс.Карты.');
                    }
                },
            ],
        ]);

        $user = auth()->user();
        $inputUrl = trim($request->yandex_maps_url);
        $cleanUrl = $inputUrl;//explode('?', $inputUrl)[0];
        if (!$cleanUrl) {
            $user->update([
                'yandex_maps_url' => null,
            ]);
            return back()->with('success', 'Интеграция отключена');
        }

        $user->update([
            'yandex_maps_url' => $cleanUrl,
        ]);

        return Inertia::render('Settings/Edit', [
            'user' => $user->fresh(),
        ])->with('success', 'Ссылка сохранена!');
    }
}
