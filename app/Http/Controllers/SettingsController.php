<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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
            'yandex_maps_url' => 'nullable|url|max:500',
        ]);

        $user = auth()->user();
        $inputUrl = trim($request->yandex_maps_url);

        if (!$inputUrl) {
            $user->update(['yandex_maps_url' => null, 'yandex_org_id' => null]);
            return redirect()->back()->with('success', 'Интеграция отключена');
        }

        $ch = curl_init($inputUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 5, // 10 многовато для синхронного запроса в контроллере
            CURLOPT_NOBODY         => true, // Нам нужен только заголовок/URL, тело качать не обязательно
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        $orgId = null;

        if ($finalUrl && $httpCode >= 200 && $httpCode < 400) {
            // Более гибкий паттерн: ищем число после /org/.../
            if (preg_match('/\/org\/[^\/?]+\/(\d+)(?:[\/?]|$)/i', $finalUrl, $matches)) {
                $orgId = $matches[1];
            } else {
                Log::warning('Yandex Maps: org_id NOT extracted', ['final_url' => $finalUrl]);
            }
        } else {
            Log::error('Yandex Maps: curl failed', [
                'http_code' => $httpCode,
                'error'     => $curlError,
                'final_url' => $finalUrl,
            ]);
        }

        auth()->user()->update([
            'yandex_maps_url' => $inputUrl,
            'yandex_org_id'   => $orgId,
        ]);

        $message = $orgId
            ? "Ссылка сохранена! ID организации: {$orgId}"
            : "Ссылка сохранена, но ID организации не удалось извлечь. Попробуйте полную ссылку на карточку.";

        return redirect()->route('settings.edit')
            ->with('success', $message)
            ->with('org_id', $orgId);  // для Vue, если хочешь отдельно выводить
    }
}
