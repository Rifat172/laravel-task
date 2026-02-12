<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $yandexUrl = $user->yandex_maps_url;

        if (!$yandexUrl) {
            return Inertia::render('Reviews/Index', [
                'yandex_url' => null,
                'reviews_data' => null,
            ]);
        }

        $parser = new \App\Services\YandexReviewsParser();
        $data = $parser->parse($yandexUrl);

        return Inertia::render('Reviews/Index', [
            'yandex_url'   => $yandexUrl,
            'reviews_data' => $data,
        ]);
    }
}
