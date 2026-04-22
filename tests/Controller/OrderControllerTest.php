<?php

namespace App\Tests\Controller;

use App\Controller\DebugController;
use App\Controller\OrderController;
use App\Entity\Orders;
use App\Repository\OrdersRepository;
use App\Service\SearchService;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class OrderControllerTest extends TestCase
{
    public function testCheckPrivileges(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SHOW GRANTS FOR CURRENT_USER()')
            ->willReturn([['Grants for current_user()' => 'GRANT USAGE ON *.* TO `tester`@`%`']]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Database privileges:', [['Grants for current_user()' => 'GRANT USAGE ON *.* TO `tester`@`%`']]);

        $controller = new DebugController($connection, $logger);
        $response = $controller->checkPrivileges();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"message":"Privileges logged successfully."}', $response->getContent());
    }

    public function testGetOneOrder(): void
    {
        $order = $this->createOrderEntity(42, '2024-06-15 14:30:22');

        $repository = $this->createMock(OrdersRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($order);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->with(Orders::class)->willReturn($repository);

        $controller = new OrderController($registry);
        $response = $controller->getOrder(42);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"id":42,"create_date":"2024-06-15 14:30:22"}',
            $response->getContent()
        );
    }

    public function testGetOrderStats(): void
    {
        $repository = $this->createMock(OrdersRepository::class);
        $repository->expects($this->once())
            ->method('getOrderStats')
            ->with(1, 10, 'month')
            ->willReturn([
                'page' => 1,
                'limit' => 10,
                'total_pages' => 3,
                'total_items' => 24,
                'group_by' => 'month',
                'data' => [
                    ['year' => 2024, 'month' => 6, 'count' => 7],
                    ['year' => 2024, 'month' => 5, 'count' => 17],
                ],
            ]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->with(Orders::class)->willReturn($repository);

        $controller = new OrderController($registry);
        $request = Request::create('/orders/stats', 'GET', [
            'page' => 1,
            'limit' => 10,
            'group_by' => 'month',
        ]);

        $response = $controller->getOrderStats($request);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['page']);
        $this->assertSame(10, $data['limit']);
        $this->assertSame(3, $data['total_pages']);
        $this->assertSame(24, $data['total_items']);
        $this->assertSame('month', $data['group_by']);
        $this->assertCount(2, $data['data']);
    }

    public function testGetOrderStatsRejectsInvalidGroupBy(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->never())->method('getRepository');

        $controller = new OrderController($registry);
        $request = Request::create('/orders/stats', 'GET', [
            'page' => 1,
            'limit' => 10,
            'group_by' => 'quarter',
        ]);

        $response = $controller->getOrderStats($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('{"error":"Invalid group_by parameter"}', $response->getContent());
    }

    public function testCreateOrder(): void
    {
        $persistedOrder = null;
        $entityManager = $this->createMock(ObjectManager::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function ($entity) use (&$persistedOrder): bool {
                $persistedOrder = $entity;

                return $entity instanceof Orders && $entity->getName() === 'Test Order';
            }));
        $entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(static function () use (&$persistedOrder): void {
                if ($persistedOrder instanceof Orders) {
                    self::setPrivateProperty($persistedOrder, 'id', 123);
                }
            });

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($entityManager);

        $controller = new OrderController($registry);
        $request = Request::create(
            '/soap',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            $this->createOrderXml()
        );

        $response = $controller->createOrder($request);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Order created', $data['message']);
        $this->assertSame(123, $data['id']);
    }

    public function testCreateOrderRejectsInvalidContentType(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->never())->method('getManager');

        $controller = new OrderController($registry);
        $request = Request::create('/soap', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '<order />');

        $response = $controller->createOrder($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid Content-Type. Expected application/xml.', $response->getContent());
    }

    public function testCreateOrderRejectsMalformedXml(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->never())->method('getManager');

        $controller = new OrderController($registry);
        $request = Request::create('/soap', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/xml'], '<order>');

        $response = $controller->createOrder($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Malformed XML.', $response->getContent());
    }

    public function testSearchOrders(): void
    {
        $query = '56';

        $searchService = $this->createMock(SearchService::class);
        $searchService
            ->method('searchOrders')
            ->with($query)
            ->willReturn([
                'hits' => [['_id' => '1', '_score' => 1]],
                'total' => 1,
                'error' => null,
                'warning' => null,
            ]);

        $controller = new OrderController($this->createMock(ManagerRegistry::class));
        $request = Request::create('/search', 'GET', ['q' => $query]);
        $response = $controller->searchOrders($request, $searchService);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['total']);
        $this->assertNull($data['error']);
        $this->assertNotEmpty($data['hits']);
    }

    private function createOrderEntity(int $id, string $createDate): Orders
    {
        $order = new Orders();
        $order->setName('Test Order');
        $order->setHash('hash-' . $id);
        $order->setToken('token-' . $id);
        $order->setLocale('en');
        $order->setCurrency('USD');
        $order->setPayType(1);
        $order->setCreateDate(new \DateTimeImmutable($createDate));

        self::setPrivateProperty($order, 'id', $id);

        return $order;
    }

    private function createOrderXml(): string
    {
        return <<<XML
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
    }

    private static function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
