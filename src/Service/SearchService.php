<?php
namespace App\Service;

use Manticoresearch\Client;

class SearchService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(['host' => 'manticore', 'port' => 9306]);
    }

    public function searchOrders(string $query): array
    {
        $index = $this->client->index('orders');
        return $index->search($query)->get();
    }
}
?>