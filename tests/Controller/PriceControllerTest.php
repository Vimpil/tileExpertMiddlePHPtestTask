<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PriceControllerTest extends WebTestCase
{
    public function testGetPrice()
    {
        $client = static::createClient();
        $client->request('GET', '/price', [
            'factory' => 'cobsa',
            'collection' => 'manual',
            'article' => 'manu7530bcbm-manualbaltic7-5x30',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        echo "Price: " . ($data['price'] ?? 'not found') . "\n";
        $this->assertArrayHasKey('price', $data, 'Price not found in response');
    }
}