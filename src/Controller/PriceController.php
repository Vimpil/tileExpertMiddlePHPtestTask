<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\PriceFetcher;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use App\DTO\PriceRequest;
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
        $priceRequest->factory = $request->query->get('factory');
        $priceRequest->collection = $request->query->get('collection');
        $priceRequest->article = $request->query->get('article');

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
        } catch (\Exception $e) {
            $this->logger->error('Error fetching price', [
                'exception' => $e,
                'factory' => $priceRequest->factory,
                'collection' => $priceRequest->collection,
                'article' => $priceRequest->article,
            ]);
            return new JsonResponse(['error' => 'An internal error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
