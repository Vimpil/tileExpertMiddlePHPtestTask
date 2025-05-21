<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Manticoresearch\Client as ManticoreClient;

/**
 * Service for searching orders using Manticore Search
 */
class SearchService
{
    private Client $httpClient;
    private string $manticoreHost;
    private int $manticorePort;

    /**
     * @param string $host Manticore search host
     * @param int $port Manticore search port
     */
    public function __construct(string $host, int $port)
    {
        $this->manticoreHost = $host;
        $this->manticorePort = $port;
        
        // Add HTTP client for direct JSON API access
        $this->httpClient = new Client();
    }

    /**
     * Search for orders matching the provided query
     *
     * @param string $query The search query
     * @param int $limit Maximum number of results to return
     * @return array Search results with hits, total count, and any errors
     */
    public function searchOrders(string $query, int $limit = 10): array
    {
        try {
            // Use direct HTTP JSON API which matches the curl approach
            $manticoreUrl = "http://{$this->manticoreHost}:{$this->manticorePort}/json/search";
            
            $requestParams = [
                'index' => 'orders',
                'limit' => $limit
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

        } catch (GuzzleException $e) {
            return [
                'hits' => [],
                'total' => 0,
                'error' => 'Network error: ' . $e->getMessage(),
                'warning' => null,
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
