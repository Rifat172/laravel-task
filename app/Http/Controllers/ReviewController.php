<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\YandexReviewsParser;

class ReviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $yandexUrl = $user->yandex_maps_url;

        if (!$yandexUrl) {
            return Inertia::render('Reviews/Index', [
                'yandex_url' => null,
                'initial_data' => null,
            ]);
        }

        $parser = new YandexReviewsParser();
        $data = $parser->parse($yandexUrl, 1);

        return Inertia::render('Reviews/Index', [
            'yandex_url'   => $yandexUrl,
            'initial_data' => $data,
        ]);
    }

    public function loadMore(Request $request)
    {
        $request->validate(
            [
                'url'   => 'required|url',
                'page'  => 'required|integer|min:2',
            ]
        );

        $parser = new YandexReviewsParser();
        $data = $parser->parse($request->url, $request->page);

        if (!$data) {
            return response()->json(['error' => 'Не удалось загрузить отзывы'], 422);
        }

        return response()->json([
            'reviews'        => $data['reviews'] ?? [],
            'has_more'       => $data['has_more'] ?? false,
            'current_page'   => $data['current_page'],
        ]);
    }
}
