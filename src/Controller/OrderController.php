<?php
namespace App\Controller;

use App\Entity\Orders;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get order statistics with pagination and grouping.
     */
    public function getOrderStats(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $groupBy = $request->query->get('group_by', 'month');

        $validGroupBy = ['day', 'month', 'year'];
        if (!in_array($groupBy, $validGroupBy, true)) {
            return new JsonResponse(['error' => 'Invalid group_by parameter'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $stats = $this->doctrine->getRepository(Orders::class)->getOrderStats($page, $limit, $groupBy);
        return new JsonResponse($stats);
    }

    /**
     * Get a single order by ID.
     */
    public function getOrder(int $id): JsonResponse
    {
        $order = $this->doctrine->getRepository(Orders::class)->find($id);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse([
            'id' => $order->getId(),
            'create_date' => $order->getCreateDate()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create a new order from XML input.
     */
    public function createOrder(Request $request): Response
    {
        // Validate Content-Type
        if (0 !== strpos($request->headers->get('Content-Type', ''), 'application/xml')) {
            return new Response('Invalid Content-Type. Expected application/xml.', 400);
        }

        // Parse XML safely
        $xml = $request->getContent();
        libxml_use_internal_errors(true);
        $data = simplexml_load_string($xml);
        if ($data === false) {
            return new Response('Malformed XML.', 400);
        }

        // Validate required fields
        if (empty($data->name)) {
            return new Response('Missing required field: name', 400);
        }

        // Map XML to entity
        $order = new Orders();
        $order->setName((string)$data->name);
        $order->setHash(uniqid());
        $order->setToken(bin2hex(random_bytes(16)));
        $order->setCreateDate(new \DateTime());
        $order->setLocale('en');
        $order->setCurrency('USD');
        $order->setPayType(1);

        // Optional fields from XML (if present)
        if (!empty($data->user_id)) $order->setUserId((int)$data->user_id);
        if (!empty($data->number)) $order->setNumber((string)$data->number);
        if (!empty($data->status)) $order->setStatus((int)$data->status);
        if (!empty($data->email)) $order->setEmail((string)$data->email);
        if (!empty($data->vat_type)) $order->setVatType((int)$data->vat_type);
        if (!empty($data->vat_number)) $order->setVatNumber((string)$data->vat_number);
        if (!empty($data->tax_number)) $order->setTaxNumber((string)$data->tax_number);
        if (!empty($data->discount)) $order->setDiscount((int)$data->discount);
        if (!empty($data->delivery)) $order->setDelivery((float)$data->delivery);
        if (!empty($data->delivery_type)) $order->setDeliveryType((int)$data->delivery_type);
        if (!empty($data->delivery_time_min)) $order->setDeliveryTimeMin(new \DateTime((string)$data->delivery_time_min));
        if (!empty($data->delivery_time_max)) $order->setDeliveryTimeMax(new \DateTime((string)$data->delivery_time_max));
        if (!empty($data->delivery_time_confirm_min)) $order->setDeliveryTimeConfirmMin(new \DateTime((string)$data->delivery_time_confirm_min));
        if (!empty($data->delivery_time_confirm_max)) $order->setDeliveryTimeConfirmMax(new \DateTime((string)$data->delivery_time_confirm_max));
        if (!empty($data->delivery_time_fast_pay_min)) $order->setDeliveryTimeFastPayMin(new \DateTime((string)$data->delivery_time_fast_pay_min));
        if (!empty($data->delivery_time_fast_pay_max)) $order->setDeliveryTimeFastPayMax(new \DateTime((string)$data->delivery_time_fast_pay_max));
        if (!empty($data->delivery_old_time_min)) $order->setDeliveryOldTimeMin(new \DateTime((string)$data->delivery_old_time_min));
        if (!empty($data->delivery_old_time_max)) $order->setDeliveryOldTimeMax(new \DateTime((string)$data->delivery_old_time_max));
        if (!empty($data->delivery_index)) $order->setDeliveryIndex((string)$data->delivery_index);
        if (!empty($data->delivery_country)) $order->setDeliveryCountry((int)$data->delivery_country);
        if (!empty($data->delivery_region)) $order->setDeliveryRegion((string)$data->delivery_region);
        if (!empty($data->delivery_city)) $order->setDeliveryCity((string)$data->delivery_city);
        if (!empty($data->delivery_address)) $order->setDeliveryAddress((string)$data->delivery_address);
        if (!empty($data->delivery_building)) $order->setDeliveryBuilding((string)$data->delivery_building);
        if (!empty($data->delivery_phone_code)) $order->setDeliveryPhoneCode((string)$data->delivery_phone_code);
        if (!empty($data->delivery_phone)) $order->setDeliveryPhone((string)$data->delivery_phone);

        // Persist using Doctrine
        $em = $this->doctrine->getManager();
        $em->persist($order);
        $em->flush();

        // Return created resource info
        return $this->json([
            'message' => 'Order created',
            'id' => $order->getId(),
        ], 201);
    }
}
