<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class YandexReviewsParser
{
    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create([
            'headers' => [
                'User-Agent' => rand(0, 1) ? 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36' : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            ],
            'timeout' => 15,
        ]);
    }

    /**
     * Парсит страницу отзывов по org_id
     *
     * @param string $orgId
     * @return array|null [rating, reviews_count, ratings_count, reviews[]]
     */
    public function parse(string $inputUrlOrOrgId): ?array
    {
        // Если передан org_id (число) — превращаем в предполагаемую ссылку
        if (is_numeric($inputUrlOrOrgId)) {
            $startUrl = "https://yandex.ru/maps/org/_/{$inputUrlOrOrgId}/";
        } else {
            $startUrl = $inputUrlOrOrgId; // короткая ссылка или полная
        }
        $cacheKey = 'yandex_reviews_' . md5($startUrl);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($startUrl) {
            try {
                // 1. Получаем финальный URL организации (после редиректа)
                $response = $this->httpClient->request('GET', $startUrl, ['max_redirects' => 10]);
                $orgPageUrl = $response->getInfo('url'); // финальный URL после всех редиректов
                Log::info('Yandex: финальный URL организации', ['url' => $orgPageUrl]);

                // 2. Ищем ссылку на отзывы прямо на странице организации
                $html = $response->getContent();
                $crawler = new Crawler($html);

                // Ищем элемент с классом tabs-select-view__title _name_reviews
                $reviewsTabLink = $crawler->filter('.tabs-select-view__title._name_reviews a')->first();

                if ($reviewsTabLink->count() === 0) {
                    Log::warning('Yandex: ссылка на отзывы не найдена', ['url' => $orgPageUrl]);
                    return null;
                }

                $reviewsPath = $reviewsTabLink->attr('href');
                $reviewsUrl = $reviewsPath ? 'https://yandex.ru' . $reviewsPath : null;

                if (!$reviewsUrl) {
                    return null;
                }

                Log::info('Yandex: найдена страница отзывов', ['reviews_url' => $reviewsUrl]);

                // 3. Парсим страницу отзывов
                $reviewsResponse = $this->httpClient->request('GET', $reviewsUrl);

                if ($reviewsResponse->getStatusCode() !== 200) {
                    Log::warning('Yandex: отзывы не загрузились', ['status' => $reviewsResponse->getStatusCode()]);
                    return null;
                }

                $reviewsHtml = $reviewsResponse->getContent();
                $crawler = new Crawler($reviewsHtml);

                $data = [
                    'rating'        => [],
                    'reviews_count' => null,
                    'ratings_count' => null,
                    'reviews'       => [],
                ];

                // Рейтинг — собираем из отдельных span внутри .business-summary-rating-badge-view__rating
                $ratingParts = [];
                $crawler->filter('.business-summary-rating-badge-view__rating .business-summary-rating-badge-view__rating-text')
                    ->each(function (Crawler $node) use (&$ratingParts) {
                        $text = trim($node->text());
                        if ($text !== '' && $text !== ',') {
                            $ratingParts[] = $text;
                        }
                    });

                if (!empty($ratingParts)) {
                    $data['rating'] = $ratingParts;
                }
                // Количество оценок — ищем span с классом _summary
                $counterNode = $crawler->filter('.business-rating-amount-view._summary')->first();
                $counterText = $counterNode->count() ? trim($counterNode->text()) : '';
                if ($counterText) {
                    preg_match('/(\d+)\s*оценок/i', $counterText, $ratMatch);
                    $data['ratings_count'] = $ratMatch[1] ?? null;;
                }

                $counterNode = $crawler->filter('.card-section-header__title._wide')->first();
                $counterText = $counterNode->count() ? trim($counterNode->text()) : '';
                if ($counterText) {
                    preg_match('/(\d+)\s*отзыв/i', $counterText, $revMatch);
                    $data['reviews_count'] = $revMatch[1] ?? null;
                }

                // Список отзывов — ищем блоки .business-review-view
                $reviews = [];
                $listContainer = $crawler->filter('.business-reviews-card-view__reviews-container')->first();

                if ($listContainer->count() > 0) {
                    $listContainer->filter('.business-review-view__info')->each(function (Crawler $node) use (&$reviews) {
                        $review = [];

                        $review['author'] = trim($node->filter('.business-review-view__author-name')->text() ?? 'Аноним');
                        $review['date']   = trim($node->filter('.business-review-view__date')->text() ?? '');
                        $dateMeta = $node->filter('meta[itemprop="datePublished"]')->first();
                        $fullDateTime = $dateMeta->count() ? $dateMeta->attr('content') : null;

                        if ($fullDateTime) {
                            $dateTimeObj = new \DateTime($fullDateTime);
                            $review['date'] = $dateTimeObj->format('d.m.Y H:i');
                        }
                        // Оценка из aria-label
                        $ratingNode = $node->filter('.business-rating-badge-view__stars');
                        $ariaLabel = $ratingNode->attr('aria-label') ?? '';
                        preg_match('/Оценка (\d+(\.\d+)?) Из 5/i', $ariaLabel, $match);
                        $review['rating'] = isset($match[1]) ? floatval($match[1]) : 0;

                        // Текст отзыва — полный, из спойлера
                        $review['text'] = trim($node->filter('.spoiler-view__text-container')->text() ?? '');

                        if ($review['text']) {
                            $reviews[] = $review;
                            // if (count($reviews) < 30) {
                            //     return;
                            // }
                            // var_dump($reviews);
                            // die();
                        } else {
                            Log::debug('Отзыв без текста пропущен', ['html' => substr($node->html(), 0, 300)]);
                        }
                    });
                } else {
                    Log::warning('Контейнер отзывов не найден', ['selector' => '.business-reviews-card-view__reviews-container']);
                }
                $data['reviews'] = $reviews;
                Log::info('Yandex парсинг результат', $data);

                return $data;
            } catch (\Exception $e) {
                Log::error('Yandex Reviews Parser exception', [
                    'message' => $e->getMessage(),
                    'url'     => $startUrl,
                ]);
                return null;
            }
        });
    }
}
