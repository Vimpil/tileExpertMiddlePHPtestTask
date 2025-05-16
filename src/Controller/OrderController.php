<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractController
{
    public function getOrderStats(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $groupBy = $request->query->get('group_by', 'month');

        $stats = $this->getDoctrine()->getRepository('App\Entity\Order')->getOrderStats($page, $limit, $groupBy);
        return new JsonResponse($stats);
    }

    public function createOrder(Request $request): Response
    {
        $xml = $request->getContent();
        $data = simplexml_load_string($xml); // Basic XML parsing
        // Save order logic here (e.g., persist to DB)
        return new Response('Order created', 200);
    }

    public function getOrder(int $id): JsonResponse
    {
        $order = $this->getDoctrine()->getRepository('App\Entity\Order')->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }
        return new JsonResponse(['id' => $order->getId(), 'create_date' => $order->getCreateDate()->format('Y-m-d H:i:s')]);
    }

    public function searchOrders(Request $request, SearchService $searchService): JsonResponse
    {
        $query = $request->query->get('q');
        $results = $searchService->searchOrders($query);
        return new JsonResponse($results);
    }
}
?>
