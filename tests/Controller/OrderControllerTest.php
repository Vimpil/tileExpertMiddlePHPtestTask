<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Deprecation Notice:
// Support for MySQL < 8 is deprecated and will be removed in Doctrine DBAL 5.
// See: https://github.com/doctrine/dbal/pull/6343
// Triggered by database connection during tests (e.g., testCheckPrivileges).
// To resolve: upgrade your MySQL server to version 8 or higher.

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
        $page = 1;
        $client->request('GET', '/orders/stats', [
            'page' => $page,
            'limit' => 10,
            'group_by' => 'month',
        ]);

        try {
            $this->assertResponseIsSuccessful();
            $content = $client->getResponse()->getContent();
            $data = json_decode($content, true);

            // Print response if assertions fail
            if (
                !isset($data['page']) ||
                !isset($data['limit']) ||
                !isset($data['total_pages']) ||
                !isset($data['data'])
            ) {
                echo "\nResponse content for debugging:\n";
                print_r($content);
            }
            print_r($data);
            $this->assertArrayHasKey('page', $data);
            $this->assertArrayHasKey('limit', $data);
            $this->assertArrayHasKey('total_pages', $data);
            $this->assertArrayHasKey('group_by', $data);
            $this->assertArrayHasKey('data', $data);
        } catch (\Exception $e) {
            if (
                $e instanceof \OutOfBoundsException ||
                (strpos($e->getMessage(), 'No more items') !== false)
            ) {
                echo "\nNo more items for page $page. This is expected for empty pages.\n";
                $this->markTestSkipped('No more items for this page.');
            } else {
                throw $e;
            }
        }
    }
}

// To see deprecation and error details when running PHPUnit, use:
//    phpunit --display-deprecations --stderr
// or add <displayDeprecations>true</displayDeprecations> in your phpunit.xml(.dist) config.
// This will show which code is using deprecated features or causing issues.

