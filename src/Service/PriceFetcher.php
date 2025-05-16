<?php
namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class PriceFetcher
{
    private Client $client;
    private const BASE_URL = 'https://tile.expert/fr/tile';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function fetchPrice(string $factory, string $collection, string $article): ?array
    {
        $url = sprintf('%s/%s/%s/a/%s', self::BASE_URL, $factory, $collection, $article);

        try {
            $response = $this->client->get($url);
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
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch price: ' . $e->getMessage());
        }
    }
}