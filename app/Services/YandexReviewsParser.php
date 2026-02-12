<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use function PHPUnit\Framework\isNull;

class YandexReviewsParser
{
    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create([
            'headers' => [
                'User-Agent' => rand(0, 1)
                    ? 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
                    : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            ],
            'timeout' => 15,
        ]);
    }

    /**
     * Основной метод — парсит отзывы по любой ссылке на Яндекс.Карты
     */
    public function parse(string $inputUrl): ?array
    {
        $cacheKey = 'yandex_reviews_' . md5($inputUrl);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($inputUrl) {
            try {
                Log::info('Yandex: парсим финальный URL', ['url' => $inputUrl]);

                // 1. Делаем запрос на финальную страницу
                $response = $this->httpClient->request('GET', $inputUrl);
                if ($response->getStatusCode() !== 200) {
                    Log::warning('Yandex: страница не загрузилась', ['status' => $response->getStatusCode()]);
                    return null;
                }

                $html = $response->getContent();
                $crawler = new Crawler($html);

                // 2. Пытаемся парсить как страницу отзывов
                $data = $this->parseReviewsPage($crawler);

                // Если ничего не нашли — пробуем найти ссылку на отзывы и парсить её
                if (isNull($data['reviews'])) {
                    Log::info('Yandex: ничего не нашли на странице, ищем таб отзывов');
                    $data = $this->tryParseReviewsTab($crawler);
                }
                Log::info('Yandex парсинг результат', $data);
                return $data;
            } catch (\Exception $e) {
                Log::error('Yandex Reviews Parser exception', [
                    'message' => $e->getMessage(),
                    'url'     => $inputUrl,
                ]);
                return null;
            }
        });
    }

    /**
     * Пытаемся парсить страницу как отзывы
     */
    private function parseReviewsPage(Crawler $crawler): array
    {
        $data = [
            'rating'        => [],
            'reviews_count' => null,
            'ratings_count' => null,
            'reviews'       => [],
        ];

        // Рейтинг
        $ratingParts = [];
        $crawler->filter('.business-summary-rating-badge-view__rating-text')
            ->each(function (Crawler $node) use (&$ratingParts) {
                $text = trim($node->text());
                if ($text !== '' && $text !== ',') {
                    $ratingParts[] = $text;
                }
            });
        if (!empty($ratingParts)) {
            $data['rating'] = $ratingParts;
        }

        // Количество оценок
        $counterNode = $crawler->filter('.business-rating-amount-view._summary')->first();
        if ($counterNode->count()) {
            preg_match('/(\d+)/', $counterNode->text(), $m);
            $data['ratings_count'] = $m[1] ?? null;
        }

        // Количество отзывов
        $headerNode = $crawler->filter('.card-section-header__title._wide')->first();

        if ($headerNode->count()) {
            preg_match('/(\d+)/', $headerNode->text(), $m);
            $data['reviews_count'] = $m[1] ?? null;
        }

        // Отзывы
        $reviews = [];
        $listContainer = $crawler->filter('.business-reviews-card-view__reviews-container')->first();
        if ($listContainer->count() > 0) {
            $listContainer->filter('.business-review-view')->each(function (Crawler $node) use (&$reviews) {
                $review = [];
                $review['author'] = trim($node->filter('.business-review-view__author-name')->text() ?? 'Аноним');
                $review['date']   = trim($node->filter('.business-review-view__date')->text() ?? '');

                $dateMeta = $node->filter('meta[itemprop="datePublished"]')->first();
                $fullDateTime = $dateMeta->count() ? $dateMeta->attr('content') : null;
                if ($fullDateTime) {
                    $dateTimeObj = new \DateTime($fullDateTime);
                    $review['date'] = $dateTimeObj->format('d.m.Y H:i');
                }

                $ratingNode = $node->filter('.business-rating-badge-view__stars');
                $ariaLabel = $ratingNode->attr('aria-label') ?? '';
                preg_match('/Оценка (\d+(\.\d+)?) Из 5/i', $ariaLabel, $match);
                $review['rating'] = isset($match[1]) ? floatval($match[1]) : 0;

                $review['text'] = trim($node->filter('.spoiler-view__text-container')->text() ?? '');

                if ($review['text']) {
                    $reviews[] = $review;
                }
            });
        }

        $data['reviews'] = $reviews;
        return $data;
    }

    /**
     * Если на странице не нашли отзывы — ищем таб "Отзывы" и парсим его ссылку
     */
    private function tryParseReviewsTab(Crawler $crawler): array
    {
        $data = [
            'rating'        => [],
            'reviews_count' => null,
            'ratings_count' => null,
            'reviews'       => [],
        ];

        // Ищем ссылку на отзывы
        $reviewsTab = $crawler->filter('.tabs-select-view__title._name_reviews a')->first();
        if ($reviewsTab->count() === 0) {
            Log::warning('Yandex: таб отзывов не найден');
            return $data;
        }

        $reviewsPath = $reviewsTab->attr('href');
        $reviewsUrl = $reviewsPath ? 'https://yandex.ru' . $reviewsPath : null;

        if (!$reviewsUrl) {
            return $data;
        }

        Log::info('Yandex: найден таб отзывов', ['url' => $reviewsUrl]);

        try {
            $response = $this->httpClient->request('GET', $reviewsUrl);
            if ($response->getStatusCode() !== 200) {
                return $data;
            }

            $html = $response->getContent();
            $crawler = new Crawler($html);
            return $this->parseReviewsPage($crawler);
        } catch (\Exception $e) {
            Log::error('Yandex: парсинг таба отзывов failed', ['error' => $e->getMessage()]);
            return $data;
        }
    }
}
