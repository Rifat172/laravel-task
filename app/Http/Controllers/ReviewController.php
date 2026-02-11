<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Reviews/Index', [
            'org_id' => $user->yandex_org_id,
            'yandex_url' => $user->yandex_maps_url,
        ]);
    }
}