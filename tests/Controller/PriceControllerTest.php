<?php

namespace App\Tests\Controller;

use App\Controller\PriceController;
use App\DTO\PriceRequest;
use App\Service\PriceFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PriceControllerTest extends TestCase
{
    public function testGetPrice(): void
    {
        $priceFetcher = $this->createMock(PriceFetcher::class);
        $priceFetcher
            ->expects($this->once())
            ->method('fetchPrice')
            ->with('cobsa', 'manual', 'manu7530bcbm-manualbaltic7-5x30')
            ->willReturn([
                'price' => '56.65',
                'factory' => 'cobsa',
                'collection' => 'manual',
                'article' => 'manu7530bcbm-manualbaltic7-5x30',
            ]);

        $controller = new PriceController($priceFetcher, $this->createMock(LoggerInterface::class));
        $response = $controller->getPrice(
            Request::create('/price', 'GET', [
                'factory' => 'cobsa',
                'collection' => 'manual',
                'article' => 'manu7530bcbm-manualbaltic7-5x30',
            ]),
            $this->createValidatingValidator()
        );

        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('price', $data);
        $this->assertSame('56.65', $data['price']);
    }

    public function testGetPriceRejectsInvalidParameters(): void
    {
        $priceFetcher = $this->createMock(PriceFetcher::class);
        $priceFetcher->expects($this->never())->method('fetchPrice');

        $controller = new PriceController($priceFetcher, $this->createMock(LoggerInterface::class));
        $response = $controller->getPrice(
            Request::create('/price', 'GET', [
                'factory' => '',
                'collection' => 'manual',
                'article' => 'manu7530bcbm-manualbaltic7-5x30',
            ]),
            $this->createInvalidValidator('factory')
        );

        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $data);
        $this->assertNotEmpty($data['errors']);
    }

    private function createValidatingValidator(): ValidatorInterface
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        return $validator;
    }

    private function createInvalidValidator(string $propertyPath): ValidatorInterface
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation(
                'This value should not be blank.',
                null,
                [],
                null,
                $propertyPath,
                ''
            ),
        ]));

        return $validator;
    }
}