<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @extends ServiceEntityRepository<Orders>
 */
#[ORM\Entity(repositoryClass: self::class)]
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    /**
     * Get order statistics with pagination and grouping by day, month, or year.
     *
     * @param int $page Page number (1-based)
     * @param int $limit Number of results per page
     * @param string $groupBy Grouping type ('day', 'month', 'year')
     * @return array{page: int, limit: int, total_pages: int, data: array}
     */
    public function getOrderStats(int $page, int $limit, string $groupBy): array
    {
        // Validate groupBy parameter
        $allowedGroupings = ['day', 'month', 'year'];
        if (!in_array($groupBy, $allowedGroupings, true)) {
            throw new \InvalidArgumentException('Invalid groupBy parameter. Use: ' . implode(', ', $allowedGroupings));
        }

        // Build the query for grouped statistics
        $qb = $this->createQueryBuilder('o');

        switch ($groupBy) {
            case 'day':
                $qb->select("DATE_FORMAT(o.createDate, '%Y-%m-%d') as date, COUNT(o.id) as count")
                    ->groupBy('date');
                $countField = "DATE_FORMAT(o.createDate, '%Y-%m-%d')";
                break;
            case 'month':
                $qb->select("DATE_FORMAT(o.createDate, '%Y-%m') as month, COUNT(o.id) as count")
                    ->groupBy('month');
                $countField = "DATE_FORMAT(o.createDate, '%Y-%m')";
                break;
            case 'year':
                $qb->select("YEAR(o.createDate) as year, COUNT(o.id) as count")
                    ->groupBy('year');
                $countField = "YEAR(o.createDate)";
                break;
        }

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Execute the query
        $results = $qb->getQuery()->getArrayResult();

        // Calculate total pages
        $totalQuery = $this->createQueryBuilder('o')
            ->select("COUNT(DISTINCT $countField) as total")
            ->getQuery();
        $total = (int) $totalQuery->getSingleScalarResult();
        $totalPages = (int) ceil($total / $limit);

        return [
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'data' => $results,
        ];
    }

    /**
     * Find an order by its ID with associated articles.
     *
     * @param int $id Order ID
     * @return Orders|null
     */
    public function findOneByIdWithArticles(int $id): ?Orders
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.articles', 'a')
            ->addSelect('a')
            ->where('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search orders by name or description for Manticore integration.
     *
     * @param string $query Search query
     * @return Orders[]
     */
    public function searchOrders(string $query): array
    {
        // Note: This method assumes Manticore returns IDs, which are then used to fetch full entities
        // For simplicity, we're using a LIKE search here; integrate with Manticore in the controller/service
        return $this->createQueryBuilder('o')
            ->where('o.name LIKE :query OR o.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save an order entity.
     *
     * @param Orders $order
     * @param bool $flush Whether to flush changes immediately
     */
    public function save(Orders $order, bool $flush = true): void
    {
        $this->getEntityManager()->persist($order);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}