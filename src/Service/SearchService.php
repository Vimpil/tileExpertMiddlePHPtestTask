<?php
namespace App\Service;

use GuzzleHttp\Client;
use Manticoresearch\Client as ManticoreClient;

class SearchService
{
    private $client;
    private $httpClient;
    private $manticoreHost;
    private $manticorePort;

    public function __construct()
    {
        $this->manticoreHost = 'myapp-manticore-1';
        $this->manticorePort = 9308;
        
        // Keep original client for compatibility
        $this->client = new ManticoreClient(['host' => $this->manticoreHost, 'port' => $this->manticorePort]);
        
        // Add HTTP client for direct JSON API access
        $this->httpClient = new Client();
    }

    public function searchOrders(string $query): array
    {
        try {
            // Use direct HTTP JSON API which matches the curl approach
            $manticoreUrl = "http://{$this->manticoreHost}:{$this->manticorePort}/json/search";
            
            $requestParams = [
                'index' => 'orders',
                'limit' => 1
            ];

            if (trim($query) === '') {
                $requestParams['query'] = ['match_all' => new \stdClass()];
            } else {
                $requestParams['query'] = [
                    'match' => [
                        'name' => $query
                    ]
                ];
            }
            
            $response = $this->httpClient->request('POST', $manticoreUrl, [
                'json' => $requestParams,
                'timeout' => 5
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return [
                    'hits' => [],
                    'total' => 0,
                    'error' => "Manticoresearch returned status code: {$statusCode}",
                    'warning' => null,
                ];
            }
            
            $rawResponse = json_decode($response->getBody()->getContents(), true);
            
            $hits = [];
            $total = 0;

            if (isset($rawResponse['hits']['hits'])) {
                $hits = $rawResponse['hits']['hits'];
            }
            if (isset($rawResponse['hits']['total'])) {
                $total = $rawResponse['hits']['total'];
            }

            return [
                'hits' => $hits,
                'total' => $total,
                'error' => null,
                'warning' => isset($rawResponse['warning']) ? $rawResponse['warning'] : null,
            ];

        } catch (\Exception $e) {
            return [
                'hits' => [],
                'total' => 0,
                'error' => 'An unexpected error occurred: ' . $e->getMessage(),
                'warning' => null,
            ];
        }
    }
}
