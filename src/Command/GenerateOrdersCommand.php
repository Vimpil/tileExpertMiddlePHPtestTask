<?php
namespace App\Command;

use App\Entity\Orders;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateOrdersCommand extends Command
{
    protected static $defaultName = 'app:generate-orders';
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct(self::$defaultName); // Explicitly set the command name
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('Generates test orders for the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        for ($i = 0; $i < 1000; $i++) {
            $order = new Orders();
            $order->setHash(uniqid());
            $order->setToken(bin2hex(random_bytes(16)));
            $order->setName('Order ' . $i);
            $order->setCreateDate(new \DateTime(sprintf('-%d days', rand(0, 365))));
            $order->setLocale('en');
            $order->setCurrency('USD');
            $order->setPayType(rand(1, 3));
            $this->entityManager->persist($order);
        }

        $this->entityManager->flush();
        $output->writeln('Generated 1000 test orders.');
        return Command::SUCCESS;
    }
}