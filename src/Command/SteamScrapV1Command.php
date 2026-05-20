<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Steam;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamScrapV1Command extends Command
{
    private OutputInterface $output;
    private float $prevTime;

    public function __construct(private int $requestTimeout, private int $batchSize, private EntityManagerInterface $entityManager, private HttpClientInterface $client)
    {
        parent::__construct('app:steam:scrap:v1');
    }

    protected function configure(): void
    {
        // prettier-ignore
        $this->setDescription('Retrieve all games from steam and put them in the steam table for autocompletion (without API key).')
             ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'http request timeout in seconds', $this->requestTimeout);
    }

    public function __invoke(OutputInterface $output, InputInterface $input): int
    {
        $this->output = $output;
        $this->prevTime = microtime(true);

        $timeout = intval($input->getOption('timeout'));

        try {
            $output->write('Starting transaction...');
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();
            $tableName = $this->entityManager->getClassMetadata(Steam::class)->getTableName();
            $this->writeDone();

            $output->write('Downloading the steam apps lists...');
            $options = ['max_duration' => $timeout];
            $response = $this->client->request('GET', 'https://api.steampowered.com/ISteamApps/GetAppList/v2/', $options);
            $response = $response->toArray()['applist'];
            $this->writeDone();

            $output->write('Deleting existing data from the table...');
            $connection->executeStatement('DELETE FROM ' . $tableName . '');
            $this->writeDone();

            $output->write('Preparing insert statements...');
            $stmtInsert = $connection->prepare('INSERT INTO ' . $tableName . ' (id, name) VALUES (:id, :name)');
            $this->writeDone();

            $output->write('Inserting all apps...');
            $countInsert = 0;
            foreach ($response['apps'] as $app) {
                if (empty($app['name'])) {
                    continue;
                }

                $stmtInsert->bindValue('id', $app['appid'], ParameterType::INTEGER);
                $stmtInsert->bindValue('name', mb_substr($app['name'], 0, 255), ParameterType::STRING);
                $stmtInsert->executeStatement();
                ++$countInsert;
                if (0 == $countInsert % $this->batchSize) {
                    $output->write(' ' . $countInsert);
                }
            }
            $output->write(0 != $countInsert % $this->batchSize ? ' ' . $countInsert : '');
            $this->writeDone();

            $output->write('Committing transaction...');
            $connection->commit();
            $this->writeDone();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            isset($connection) && $connection->isConnected() ? $connection->rollBack() : null;
            $output->writeln(' Failed.');
            if ($input->getOption('verbose')) {
                $output->write($e->getMessage());
            }

            return Command::FAILURE;
        }
    }

    private function writeDone(): void
    {
        $this->output->writeln(' Done.' . $this->getTime());
    }

    private function getTime(): string
    {
        $nextTime = microtime(true);
        $interval = round($nextTime - $this->prevTime, 2);
        $this->prevTime = $nextTime;

        return ' (' . sprintf('%.2F', $interval) . 's) ';
    }
}
