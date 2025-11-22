<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Steam;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamScrapV2Command extends Command
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
        parent::__construct('steam:scrap:v2');
    }

    protected function configure(): void
    {
        $this->setDescription('Retrieve all games from steam and put them in the steam table for autocompletion (with API key).')
            ->addArgument('mode', InputArgument::REQUIRED, 'truncate = reset and insert all games | update = update games if present, insert otherwised')
            ->addOption('since', 's', InputOption::VALUE_REQUIRED, 'with update mode, only update games modified since this date', 0);
    }

    public function __invoke(OutputInterface $output, InputInterface $input): int
    {
        $this->output = $output;
        $this->prevTime = microtime(true);

        $mode = $input->getArgument('mode');
        if (!in_array($mode, ['truncate', 'update'])) {
            $output->writeln('The mode argument must be either truncate or update.');

            return Command::INVALID;
        }

        $since = intval($input->getOption('since'));
        $since = 'update' == $mode ? $since : null;
        if ($since < 0 || $since > time()) {
            $output->writeln('The --since option must be between 0 and the current timestamp.');

            return Command::INVALID;
        }

        try {
            $output->write('Starting transaction...');
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();
            $tableName = $this->entityManager->getClassMetadata(Steam::class)->getTableName();
            $this->writeDone();

            if ('truncate' == $mode) {
                $output->write('Deleting existing data from the table...');
                $connection->executeStatement('DELETE FROM ' . $tableName . '');
                $this->writeDone();
            }

            $output->write('Preparing insert statements...');
            $stmtInsert = $connection->prepare('INSERT INTO ' . $tableName . ' (id, name) VALUES (:id, :name)');
            $this->writeDone();

            if ('update' == $mode) {
                $output->write('Preparing update statements and retrieving knowned ids...');
                $stmtUpdate = $connection->prepare('UPDATE ' . $tableName . ' SET name = :name WHERE id = :id');
                $knownedIds = $connection->executeQuery('SELECT id FROM ' . $tableName)->fetchAllAssociativeIndexed();
                $knownedIds = array_keys($knownedIds);
                $this->writeDone();
            }

            $output->writeln('Inserting and updating all apps...');
            $query = [
                'key' => $this->steamApiKey,
                'max_results' => $this->batchSize,
                'if_modified_since' => $since,
                'include_dlc' => true,
                'include_software' => true,
                'last_appid' => 0,
            ];
            $options = ['max_duration' => $this->requestTimeout];
            $countBatch = 0;
            $countInsert = 0;
            $countUpdate = 0;
            do {
                ++$countBatch;
                $output->write('  Batch ' . $countBatch . ' : steam query...');
                $response = $this->client->request('GET', 'https://api.steampowered.com/IStoreService/GetAppList/v1/?' . http_build_query($query), $options);
                $response = $response->toArray()['response'];
                $this->writeOk();

                $output->write('database queries...');
                foreach ($response['apps'] as $app) {
                    if (empty($app['name'])) {
                        continue;
                    }

                    $params = [':id' => $app['appid'], ':name' => mb_substr($app['name'], 0, 255)];
                    if ('update' == $mode && in_array($app['appid'], $knownedIds)) {
                        $stmtUpdate->executeStatement($params);
                        ++$countUpdate;
                    } else {
                        $stmtInsert->executeStatement($params);
                        ++$countInsert;
                    }
                }
                $this->writeOk();
                $output->writeln('');

                $query['last_appid'] = $response['last_appid'] ?? 0;
            } while (!empty($response['have_more_results']));
            $output->writeln('  Total : ' . $countInsert . ' apps inserted, ' . $countUpdate . ' apps updated. Done.');

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
