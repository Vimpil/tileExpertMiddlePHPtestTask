<?php
namespace App\Service;

use App\Exception\ExternalPriceSourceException;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class PriceFetcher
{
    public function __construct(
        private Client $client,
        private string $baseUrl,
        private float $timeout,
    ) {
    }

    public function fetchPrice(string $factory, string $collection, string $article): ?array
    {
        $url = sprintf('%s/%s/%s/a/%s', rtrim($this->baseUrl, '/'), $factory, $collection, $article);

        try {
            $response = $this->client->get($url, [
                'timeout' => $this->timeout,
                'connect_timeout' => $this->timeout,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new ExternalPriceSourceException(sprintf('Price source returned HTTP %d for %s', $response->getStatusCode(), $url));
            }

            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $priceNode = $crawler->filter('.js-price-tag')->first();
            $price = $priceNode->count() ? $priceNode->attr('data-price-raw') : null;

            if ($price === null) {
                return null;
            }

            return [
                'price' => $price,
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
            ];
        } catch (\Throwable $e) {
            throw new ExternalPriceSourceException('Failed to fetch price from the external provider.', 0, $e);
        }
    }
}