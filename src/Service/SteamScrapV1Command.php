<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Steam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamScrapV1Command extends Command
{
    private OutputInterface $output;
    private float $prevTime;

    public function __construct(private int $requestTimeout, private int $batchSize, private EntityManagerInterface $entityManager, private HttpClientInterface $client)
    {
        parent::__construct('steam:scrap:v1');
    }

    protected function configure(): void
    {
        $this->setDescription('Retrieve all games from steam and put them in the steam table for autocompletion (without API key).');
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->output = $output;
        $this->prevTime = microtime(true);

        try {
            $output->write('Starting transaction...');
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();
            $tableName = $this->entityManager->getClassMetadata(Steam::class)->getTableName();
            $this->writeDone();

            $output->write('Downloading the steam apps lists...');
            $options = ['max_duration' => $this->requestTimeout];
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
                $params = ['id' => $app['appid'], 'name' => mb_substr($app['name'], 0, 255)];
                $stmtInsert->executeStatement($params);
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
            $connection->rollBack();
            $output->writeln(' Failed.');
            $output->write($e->getMessage());

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
