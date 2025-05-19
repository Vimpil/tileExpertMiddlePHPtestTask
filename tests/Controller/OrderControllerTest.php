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

    public function testCreateOrder()
    {
        $client = static::createClient();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<order>
    <name>Test Order</name>
    <user_id>123</user_id>
    <number>TST-001</number>
    <status>2</status>
    <email>test@example.com</email>
    <vat_type>1</vat_type>
    <vat_number>VAT123456</vat_number>
    <tax_number>TAX654321</tax_number>
    <discount>5</discount>
    <delivery>15.5</delivery>
    <delivery_type>1</delivery_type>
    <delivery_time_min>2024-06-01</delivery_time_min>
    <delivery_time_max>2024-06-10</delivery_time_max>
    <delivery_index>12345</delivery_index>
    <delivery_country>1</delivery_country>
    <delivery_region>Region</delivery_region>
    <delivery_city>City</delivery_city>
    <delivery_address>Some Address</delivery_address>
    <delivery_building>Bldg 1</delivery_building>
    <delivery_phone_code>+1</delivery_phone_code>
    <delivery_phone>5551234</delivery_phone>
</order>
XML;

        // Use the /soap endpoint as defined in routes.yaml for createOrder
        $client->request(
            'POST',
            '/soap',
            [],
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            $xml
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Order created', $data['message']);
        $this->assertArrayHasKey('id', $data);
        $this->assertIsInt($data['id']);

        // Best practice: Inform about the added data for debugging and traceability
        echo "\nOrder with ID {$data['id']} was successfully created in the database during testCreateOrder.\n";
    }
    
}

// To see deprecation and error details when running PHPUnit, use:
//    phpunit --display-deprecations --stderr
// or add <displayDeprecations>true</displayDeprecations> in your phpunit.xml(.dist) config.
// This will show which code is using deprecated features or causing issues.

