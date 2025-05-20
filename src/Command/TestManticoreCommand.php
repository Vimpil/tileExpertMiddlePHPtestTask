<?php
namespace App\Command;

use GuzzleHttp\Client;
use Manticoresearch\Client as ManticoreClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestManticoreCommand extends Command
{
    private $manticoreClient;
    private $httpClient;
    private $host;
    private $port;

    public function __construct(ManticoreClient $manticoreClient)
    {
        parent::__construct('app:test-manticore');
        $this->manticoreClient = $manticoreClient;
        $this->httpClient = new Client();
        
        // Get these values from the same place they're defined in services.yaml
        $this->host = 'myapp-manticore-1';
        $this->port = 9308;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Output connection details for debugging
            $output->writeln("Connecting to: {$this->host}:{$this->port}");
            
            $params = [
                'index' => 'orders',
                'query' => ['match_all' => new \stdClass()],
                'limit' => 5
            ];
            
            // Debug query parameters
            $output->writeln("Query parameters: " . json_encode($params));
            
            // Use direct HTTP JSON API to match curl behavior
            $manticoreUrl = "http://{$this->host}:{$this->port}/json/search";
            $response = $this->httpClient->request('POST', $manticoreUrl, [
                'json' => $params,
                'timeout' => 5
            ]);
            
            $rawResponse = json_decode($response->getBody()->getContents(), true);
            
            // Debug raw response
            $output->writeln("Raw response: " . json_encode($rawResponse));

            $hits = [];
            $total = 0;
            $warning = null;

            if (isset($rawResponse['hits']['hits'])) {
                $hits = $rawResponse['hits']['hits'];
            }
            if (isset($rawResponse['hits']['total'])) {
                $total = $rawResponse['hits']['total'];
            }
            if (isset($rawResponse['warning'])) {
                $warning = $rawResponse['warning'];
            }

            $result = [
                'hits' => $hits,
                'total' => $total,
                'error' => null,
                'warning' => $warning,
            ];

            $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $output->writeln('Connection Error: ' . $e->getMessage());
            $output->writeln('Check if Manticore is running and the hostname is correct.');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
            $output->writeln('Exception type: ' . get_class($e));
            $output->writeln('Stack trace:');
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
