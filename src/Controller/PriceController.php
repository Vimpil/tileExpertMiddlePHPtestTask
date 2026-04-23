<?php
namespace App\Controller;

use App\Exception\ExternalPriceSourceException;
use App\Service\PriceFetcher;
use App\DTO\PriceRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PriceController extends AbstractController
{
    private PriceFetcher $priceFetcher;
    private LoggerInterface $logger;

    public function __construct(PriceFetcher $priceFetcher, LoggerInterface $logger)
    {
        $this->priceFetcher = $priceFetcher;
        $this->logger = $logger;
    }

    public function getPrice(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $priceRequest = new PriceRequest();
        $priceRequest->factory = (string) $request->query->get('factory', '');
        $priceRequest->collection = (string) $request->query->get('collection', '');
        $priceRequest->article = (string) $request->query->get('article', '');

        $errors = $validator->validate($priceRequest);

        if (count($errors) > 0) {
            $errorMessages = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors));
            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $priceData = $this->priceFetcher->fetchPrice(
                $priceRequest->factory,
                $priceRequest->collection,
                $priceRequest->article
            );

            if (!$priceData) {
                return new JsonResponse(['error' => 'Price not found'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($priceData, Response::HTTP_OK);
        } catch (ExternalPriceSourceException $e) {
            $this->logger->error('Error fetching price', [
                'exception' => $e,
                'factory' => $priceRequest->factory,
                'collection' => $priceRequest->collection,
                'article' => $priceRequest->article,
            ]);
            return new JsonResponse([
                'price' => null,
                'factory' => $priceRequest->factory,
                'collection' => $priceRequest->collection,
                'article' => $priceRequest->article,
                'source_status' => 'unavailable',
                'warning' => 'Price source is temporarily unavailable. Please try again later.',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error fetching price', [
                'exception' => $e,
                'factory' => $priceRequest->factory,
                'collection' => $priceRequest->collection,
                'article' => $priceRequest->article,
            ]);
            return new JsonResponse([
                'price' => null,
                'factory' => $priceRequest->factory,
                'collection' => $priceRequest->collection,
                'article' => $priceRequest->article,
                'source_status' => 'unavailable',
                'warning' => 'Price source is temporarily unavailable. Please try again later.',
            ], Response::HTTP_OK);
        }
    }
}
