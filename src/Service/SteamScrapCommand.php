<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Steam;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'steam:scrap', description: 'Retrieve all games from steam and put them in the steam table for autocompletion.')]
class SteamScrapCommand
{
    private OutputInterface $output;
    private Connection $connection;
    private int $batchSize;
    private float $prevTime;

    public function __construct(private int $timeout, private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->connection = $this->entityManager->getConnection();
        $this->output = $output;
        $this->batchSize = 10000;
        $this->prevTime = microtime(true);

        try {
            $this->output->write('Starting transaction...');
            $this->connection->beginTransaction();
            $this->writeTime();

            $this->output->write('Downloading the steam apps lists...');
            $file = file_get_contents('/home/darkirby/temp/games_appid.json');
            $this->writeTime();

            $this->output->write('Parsing the response into JSON...');
            $appList = json_decode($file, true);
            $this->writeTime();

            $this->output->write('Deleting existing data from the table...');
            $this->entityManager->createQuery('DELETE FROM ' . Steam::class)->execute();
            $this->writeTime();

            $this->output->writeln('Inserting data in batch (' . count($appList) . ' apps found)...');
            $prevCount = 0;
            $count = 0;
            foreach ($appList as $app) {
                if (empty($app['name'])) {
                    continue;
                }

                ++$count;
                $steam = (new Steam())
                    ->setId($app['appid'])
                    ->setName(mb_substr($app['name'], 0, 255));
                $this->entityManager->persist($steam);

                if (0 == $count % $this->batchSize) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $this->output->write('  batch ' . $prevCount . ' to ' . $count);
                    $prevCount = $count + 1;
                    $this->writeTime(false);
                }
            }
            $this->entityManager->flush();
            $this->output->write('  batch ' . $prevCount . ' to ' . $count);
            $this->writeTime(false);
            $this->output->writeln('  Done.');

            $this->output->write('Committing transaction...');
            $this->connection->commit();
            $this->writeTime();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->output->writeln(' Failed.');
            $this->output->write($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function writeTime(bool $withDone = true): void
    {
        $nextTime = microtime(true);
        $interval = round($nextTime - $this->prevTime, 2);
        $this->output->writeln(' (' . $interval . 's)' . ($withDone ? ' Done.' : ''));
        $this->prevTime = $nextTime;
    }
}
