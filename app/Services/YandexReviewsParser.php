<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class YandexReviewsParser
{
    protected $httpClient;

    private static array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        // 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        // 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        // 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
    ];

    public function __construct()
    {
        $this->httpClient = HttpClient::create([
            'headers' => [
                'User-Agent' => self::$userAgents[array_rand(self::$userAgents)],
                'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            ],
            'timeout' => 15,
        ]);
    }
    private function extractOrgIdFromReviewsUrl(string $pathOrUrl): ?string
    {
        if (preg_match('#/(\d{9,})(?:/|\?|$)#', $pathOrUrl, $m)) {
            return $m[1];
        }
        return null;
    }
    private function getMainPageCrawler(string $inputUrl): ?Crawler
    {
        try {
            $response = $this->httpClient->request('GET', $inputUrl);
            if ($response->getStatusCode() !== 200) return null;
            return new Crawler($response->getContent());
        } catch (\Exception $e) {
            return null;
        }
    }
    private function parseAndEnrich(Crawler $crawler, int $page): array
    {
        $data = $this->parseReviewsPage($crawler);
        $data['current_page'] = $page;
        $data['has_more'] = $page < ($data['pages_count'] ?? 1);
        return $data;
    }
    /**
     * Основной метод — парсит отзывы по любой ссылке на Яндекс.Карты
     */
    public function parse(string $inputUrl, $page = 1): ?array
    {
        $baseUrl = Cache::get("yandex_reviews_baseurl_" . md5($inputUrl));

        if ($baseUrl) {
            $inputUrl = 'https://yandex.ru' . $baseUrl;
        }
        $orgId = null;

        if (str_contains($inputUrl, '/reviews/')) {
            $orgId = $this->extractOrgIdFromReviewsUrl($inputUrl);
        }

        if (!$orgId && $page === 1) {
            $tempCrawler = $this->getMainPageCrawler($inputUrl);
            if ($tempCrawler) {
                $reviewsTab = $tempCrawler->filter('.tabs-select-view__title._name_reviews a')->first();
                if ($reviewsTab->count()) {
                    $reviewsPath = $reviewsTab->attr('href');
                    if ($page === 1 && str_starts_with($inputUrl, 'https://yandex.ru/maps/')) {
                        Cache::rememberForever(
                            "yandex_reviews_baseurl_" . md5($inputUrl),
                            fn() => $reviewsPath
                        );
                    }
                    $orgId = $this->extractOrgIdFromReviewsUrl($reviewsPath);
                }
            }
        }

        $cacheBase = $orgId ? "org_{$orgId}" : md5($inputUrl);
        $cacheKey = "yandex_reviews_{$page}_{$cacheBase}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($inputUrl, $page, $orgId) {
            try {
                if ($orgId && $page > 1) {
                    $reviewsUrl = $inputUrl . '?page=' . $page;
                } else {
                    $crawler = $this->getReviewsPageCrawler($inputUrl, $page);
                    if (!$crawler) return null;
                    return $this->parseAndEnrich($crawler, $page);
                }

                $response = $this->httpClient->request('GET', $reviewsUrl);
                if ($response->getStatusCode() !== 200) return null;

                $crawler = new Crawler($response->getContent());
                return $this->parseAndEnrich($crawler, $page);
            } catch (\Exception $e) {
                Log::error('Yandex parse failed', ['url' => $inputUrl, 'page' => $page, 'error' => $e->getMessage()]);
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
            'pages_count'   => null,
        ];

        // Рейтинг
        $ratingParts = [];
        usleep(200000);
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
        usleep(200000);
        // Количество отзывов
        $headerNode = $crawler->filter('.card-section-header__title._wide')->first();

        if ($headerNode->count()) {
            preg_match('/(\d+)/', $headerNode->text(), $m);
            $data['reviews_count'] = $m[1] ?? null;
        }

        $data['pages_count'] = ceil($data['reviews_count'] / 50);
        // Отзывы
        $reviews = [];
        usleep(200000);
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
    private function getReviewsPageCrawler(string $inputUrl, int $page): ?Crawler
    {
        if (str_contains($inputUrl, '/reviews/') || str_contains($inputUrl, '?page=')) {
            $url = $inputUrl;
        } else {
            // Ищем таб "Отзывы"
            $response = $this->httpClient->request('GET', $inputUrl);
            if ($response->getStatusCode() !== 200) return null;

            $crawler = new Crawler($response->getContent());
            $reviewsTab = $crawler->filter('.tabs-select-view__title._name_reviews a')->first();

            if ($reviewsTab->count() === 0) {
                Log::warning('Таб отзывов не найден', ['url' => $inputUrl]);
                return null;
            }

            $reviewsPath = $reviewsTab->attr('href');
            $url = 'https://yandex.ru' . $reviewsPath;
        }

        $urlWithPage = $url . (str_contains($url, '?') ? '&' : '?') . 'page=' . $page;

        $response = $this->httpClient->request('GET', $urlWithPage);
        if ($response->getStatusCode() !== 200) {
            Log::warning('Не удалось загрузить страницу отзывов', ['url' => $urlWithPage]);
            return null;
        }

        return new Crawler($response->getContent());
    }
}
