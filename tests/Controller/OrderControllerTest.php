<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    public function testCheckPrivileges()
    {
        $client = static::createClient();
        $client->request('GET', '/debug/check-privileges');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Privileges logged successfully.', $data['message']);
    }
    public function testGetOrderStats()
    {
        $client = static::createClient();
        $client->request('GET', '/orders/stats', [
            'page' => 1,
            'limit' => 10,
            'group_by' => 'month',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('total_pages', $data);
        $this->assertArrayHasKey('data', $data);
    }
}