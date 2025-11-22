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

    public function __construct(private int $timeout, private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->connection = $this->entityManager->getConnection();
        $this->output = $output;
        $this->batchSize = 10000;

        try {
            $this->output->write('Starting transaction...');
            $this->connection->beginTransaction();
            $this->output->writeln(' Done.');

            $this->output->write('Downloading the steam apps lists...');
            $file = file_get_contents('/home/darkirby/temp/steamapps2.txt');
            $this->output->writeln(' Done.');

            $this->output->write('Parsing the response into JSON...');
            $appList = json_decode($file, true)['applist']['apps'];
            $this->output->writeln(' Done.');

            $this->output->write('Deleting existing data from the table...');
            $this->entityManager->createQuery('DELETE FROM ' . Steam::class)->execute();
            $this->output->writeln(' Done.');

            $this->output->write('Inserting data in batch (' . count($appList) . ' apps found)...');
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
                    $this->output->write(' ' . $count);
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
            $this->entityManager->flush();
            $this->output->writeln(' ' . $count . ' Done.');

            $this->output->write('Committing transaction...');
            $this->connection->commit();
            $this->output->writeln(' Done.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->output->writeln(' Failed.');
            $this->output->write($e->getMessage());

            return Command::FAILURE;
        }
    }
}
