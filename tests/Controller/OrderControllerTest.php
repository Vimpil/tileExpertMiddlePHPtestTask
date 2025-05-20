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
    public function testGetOneOrder()
    {
        $client = static::createClient();

        // Test 404 for non-existent order
        $client->request('GET', '/orders/2');
        if ($client->getResponse()->getStatusCode() === 200) {
            $orderData = json_decode($client->getResponse()->getContent(), true);
            if (!empty($orderData)) {
                print_r($orderData);
            }
            $this->assertArrayHasKey('id', $orderData);
            $this->assertArrayHasKey('create_date', $orderData);
            $this->assertJson($client->getResponse()->getContent());
        } else {
            $notFoundData = json_decode($client->getResponse()->getContent(), true);
            echo "Order not found response: " . print_r($notFoundData, true) . "\n";
            $this->assertArrayHasKey('error', $notFoundData);
            $this->assertEquals('Order not found', $notFoundData['error']);
            $this->fail('Order not found, test failed.');
        }
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
                $this->fail('No more items for this page. Test failed.');
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
        try {
            $this->assertArrayHasKey('message', $data);
            $this->assertEquals('Order created', $data['message']);
            $this->assertArrayHasKey('id', $data);
            $this->assertIsInt($data['id']);
            // Best practice: Inform about the added data for debugging and traceability
            echo "\nOrder with ID {$data['id']} was successfully created in the database during testCreateOrder.\n";
        } catch (\Throwable $e) {
            $this->fail('Order creation response invalid: ' . $e->getMessage());
        }
    }

    public function testSearchOrders(): void
    {
        $client = static::createClient();
        $query = '56'; // Use empty query to match all documents

        $client->request('GET', '/search', ['q' => $query]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertJson($content);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('hits', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('warning', $data);

        // Check that there's no error from Manticore
        $this->assertNull($data['error'], 'Search service reported an error: ' . ($data['error'] ?? 'null'));

        if (empty($data['hits']) || $data['total'] === 0) {
            $this->fail('No search results found for the given query.');
        }

        // Main check: expect total > 0 for match_all query
        $this->assertGreaterThan(0, $data['total'], 'Expected search results for empty query (should match all)');
        $this->assertIsArray($data['hits']);
        $this->assertNotEmpty($data['hits'], 'Hits array should not be empty if total > 0');

        print_r($data);
        printf(
            "%s passed | query: %s, result_count: %d\n",
            __METHOD__,
            $query,
            is_array($data['hits']) ? count($data['hits']) : 0
        );
    }
}
