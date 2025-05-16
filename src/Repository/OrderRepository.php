<?php
namespace App\Repository;

use App\Entity\Order;
use Doctrine\ORM\EntityRepository;

class OrderRepository extends EntityRepository
{
    public function getOrderStats(int $page, int $limit, string $groupBy): array
    {
        $qb = $this->createQueryBuilder('o');
        switch ($groupBy) {
            case 'day':
                $qb->select('DATE(o.createDate) as date, COUNT(o.id) as count')
                   ->groupBy('DATE(o.createDate)');
                break;
            case 'month':
                $qb->select('MONTH(o.createDate) as month, YEAR(o.createDate) as year, COUNT(o.id) as count')
                   ->groupBy('month, year');
                break;
            case 'year':
                $qb->select('YEAR(o.createDate) as year, COUNT(o.id) as count')
                   ->groupBy('year');
                break;
        }
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        $results = $qb->getQuery()->getResult();

        $totalQuery = $this->createQueryBuilder('o')->select("COUNT(DISTINCT {$groupBy}(o.createDate))");
        $total = $totalQuery->getQuery()->getSingleScalarResult();
        $pages = ceil($total / $limit);

        return [
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $pages,
            'data' => $results,
        ];
    }
}
?>
