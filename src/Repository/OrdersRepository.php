<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orders>
 */
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
     * @param \DateTimeInterface|null $startDate Optional start date filter
     * @param \DateTimeInterface|null $endDate Optional end date filter
     * @return array{page: int, limit: int, total_pages: int, total_items: int, data: array}
     * @throws \InvalidArgumentException If groupBy is invalid or page/limit is invalid
     */
    public function getOrderStats(
        int $page,
        int $limit,
        string $groupBy,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        // Validate inputs
        $allowedGroupings = ['day', 'month', 'year'];
        if (!in_array($groupBy, $allowedGroupings, true)) {
            throw new \InvalidArgumentException('Invalid groupBy parameter. Use: ' . implode(', ', $allowedGroupings));
        }
        if ($page < 1) {
            throw new \InvalidArgumentException('Page must be at least 1');
        }
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be at least 1');
        }

        // Build query for grouped statistics
        $qb = $this->createQueryBuilder('o');

        switch ($groupBy) {
            case 'day':
                $qb->select("SUBSTRING(o.createDate, 1, 10) AS period, COUNT(o.id) AS order_count");
                $periodAlias = "period";
                break;
            case 'month':
                $qb->select("SUBSTRING(o.createDate, 1, 7) AS period, COUNT(o.id) AS order_count");
                $periodAlias = "period";
                break;
            case 'year':
                $qb->select("SUBSTRING(o.createDate, 1, 4) AS period, COUNT(o.id) AS order_count");
                $periodAlias = "period";
                break;
        }

        // Apply date filters
        if ($startDate) {
            $qb->andWhere('o.createDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('o.createDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        // Group and order by the alias
        $qb->groupBy($periodAlias)
            ->orderBy($periodAlias, 'DESC');

        // Count total groups for pagination
        $countQb = $this->createQueryBuilder('o');
        switch ($groupBy) {
            case 'day':
                $countExpr = "SUBSTRING(o.createDate, 1, 10)";
                break;
            case 'month':
                $countExpr = "SUBSTRING(o.createDate, 1, 7)";
                break;
            case 'year':
                $countExpr = "SUBSTRING(o.createDate, 1, 4)";
                break;
        }
        $countQb->select("COUNT(DISTINCT $countExpr) AS total");
        if ($startDate) {
            $countQb->andWhere('o.createDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $countQb->andWhere('o.createDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($total / $limit);

        // Add total items (all orders matching filters)
        $totalItemsQb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)');
        if ($startDate) {
            $totalItemsQb->andWhere('o.createDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $totalItemsQb->andWhere('o.createDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        $totalItems = (int) $totalItemsQb->getQuery()->getSingleScalarResult();

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Execute query
        $results = $qb->getQuery()->getArrayResult();

        // Restriction: if page > 1 and no results, indicate no more items
        if (empty($results)) {
            throw new \OutOfBoundsException('No more items.');
        }

        // Format data consistently
        $data = array_map(function ($row) use ($groupBy) {
            if ($groupBy === 'day') {
                return [
                    'period' => $row['period'], // e.g., '2025-05-18'
                    'count' => (int) $row['order_count'],
                ];
            } elseif ($groupBy === 'month') {
                [$year, $month] = explode('-', $row['period']);
                return [
                    'year' => (int) $year,
                    'month' => (int) $month,
                    'count' => (int) $row['order_count'],
                ];
            } else {
                return [
                    'year' => (int) $row['period'],
                    'count' => (int) $row['order_count'],
                ];
            }
        }, $results);

        return [
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'group_by' => $groupBy,
            'data' => $data,
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
