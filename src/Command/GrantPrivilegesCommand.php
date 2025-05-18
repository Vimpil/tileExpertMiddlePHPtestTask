<?php
namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GrantPrivilegesCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct('app:grant-privileges'); // Explicitly set the command name
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->setDescription('Grants privileges to a database user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = "GRANT ALL PRIVILEGES ON my_database.* TO 'user'@'%'; FLUSH PRIVILEGES;";
        try {
            $this->connection->executeStatement($sql);
            $output->writeln('Privileges granted successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}