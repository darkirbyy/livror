<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'steam:scrap', description: 'Retrieve all games from steam and put them in the steam table for autocompletion.')]
class SteamScrapCommand
{
    private OutputInterface $output;

    public function __construct(private int $timeout)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->output = $output;

        $output->write('Downloading the steam apps lists...');
        $file = file_get_contents('/home/darkirby/temp/steamapps.txt');
        $this->checkCondition(empty($file));

        $output->write('Parsing the response into JSON...');
        $list = json_decode($file, true);
        $this->checkCondition(false === $list || !isset($list['applist']['apps']));

        $output->write('Test :' . $list['applist']['apps']['226912']['name']);

        return Command::SUCCESS;
    }

    private function checkCondition(bool $condition)
    {
        if ($condition) {
            $this->output->writeln(' Failed.');

            return Command::FAILURE;
        }
        $this->output->writeln(' Done.');
    }
}
