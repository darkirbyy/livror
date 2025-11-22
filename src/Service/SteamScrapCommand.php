<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Steam;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'steam:scrap', description: 'Retrieve all games from steam and put them in the steam table for autocompletion.')]
class SteamScrapCommand
{
    private OutputInterface $output;
    private Connection $connection;
    private float $prevTime;

    public function __construct(
        private int $requestTimeout,
        private int $batchSize,
        private string $steamApiKey,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $client,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->connection = $this->entityManager->getConnection();
        $this->output = $output;
        $this->prevTime = microtime(true);

        try {
            $this->output->write('Starting transaction...');
            $this->connection->beginTransaction();
            $this->writeDone();

            $this->output->write('Deleting existing data from the table...');
            $tableName = $this->entityManager->getClassMetadata(Steam::class)->getTableName();
            $this->connection->executeStatement('DELETE FROM ' . $tableName . '');
            $this->writeDone();

            $this->output->write('Preparing insert statement...');
            $stmt = $this->connection->prepare('INSERT INTO ' . $tableName . ' (id, name) VALUES (:id, :name)');
            $this->writeDone();

            $this->output->writeln('Inserting all apps...');
            $query = ['key' => $this->steamApiKey, 'max_results' => $this->batchSize, 'include_dlc' => true, 'last_appid' => 0];
            $options = ['max_duration' => $this->requestTimeout];
            $batch = 0;
            $count = 0;
            do {
                ++$batch;
                $this->output->write('  Batch ' . $batch . ' : Query...');
                $response = $this->client->request('GET', 'https://api.steampowered.com/IStoreService/GetAppList/v1/?' . http_build_query($query), $options);
                $response = $response->toArray()['response'];
                $this->writeOk();

                $this->output->write(' Insert...');
                foreach ($response['apps'] as $app) {
                    if (empty($app['name'])) {
                        continue;
                    }
                    ++$count;
                    $stmt->executeStatement([':id' => $app['appid'], ':name' => mb_substr($app['name'], 0, 255)]);
                }
                $this->writeOk();
                $this->output->writeln('');

                $query['last_appid'] = $response['last_appid'] ?? 0;
            } while (!empty($response['have_more_results']));
            $this->output->writeln('  Total : ' . $count . ' apps inserted, Done.');

            $this->output->write('Committing transaction...');
            $this->connection->commit();
            $this->writeDone();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->output->writeln(' Failed.');
            $this->output->write($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function writeOk(): void
    {
        $this->output->write(' OK' . $this->getTime());
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
