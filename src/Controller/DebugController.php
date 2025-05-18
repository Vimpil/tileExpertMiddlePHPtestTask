<?php
// src/Controller/DebugController.php
namespace App\Controller;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DebugController extends AbstractController
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function checkPrivileges(): JsonResponse
    {
        try {
            $result = $this->connection->fetchAllAssociative('SHOW GRANTS FOR CURRENT_USER()');
            $this->logger->info('Database privileges:', $result);

            return new JsonResponse(['message' => 'Privileges logged successfully.']);
        } catch (\Exception $e) {
            $this->logger->error('Error checking privileges: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to check privileges.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}