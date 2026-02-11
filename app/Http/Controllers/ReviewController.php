<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $orgIdOrUrl = $user->yandex_org_id ?? $user->yandex_maps_url;

        if (!$orgIdOrUrl) {
            return Inertia::render('Reviews/Index', [
                'org_id' => null,
                'reviews_data' => null,
            ]);
        }

        $parser = new \App\Services\YandexReviewsParser();
        $data = $parser->parse($orgIdOrUrl);

        return Inertia::render('Reviews/Index', [
            'org_id'       => $user->yandex_org_id,
            'reviews_data' => $data,
            'yandex_url'   => $user->yandex_maps_url
        ]);
    }
}
