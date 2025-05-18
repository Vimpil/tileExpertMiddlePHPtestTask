<?php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\Orders;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractController
{
    private ManagerRegistry $doctrine;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param Request $request
     * @return JsonResponse
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

    public function getOrder(int $id): JsonResponse
    {
        $order = $this->doctrine->getRepository(Orders::class)->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }
        return new JsonResponse(['id' => $order->getId(), 'create_date' => $order->getCreateDate()->format('Y-m-d H:i:s')]);
    }


}
?>
