<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Service\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class DiscordNotifyCommand extends Command
{
    public function __construct(
        private int $requestTimeout,
        private string $discordWebhookUrl,
        private int $discordWebhookLimit,
        private UserManager $userManager,
        private GameRepository $gameRepo,
        private ReviewRepository $reviewRepo,
        private Environment $twig,
        private HttpClientInterface $client,
    ) {
        parent::__construct('discord:notify');
    }

    protected function configure(): void
    {
        $this->setDescription('')
            ->addOption('since', 's', InputOption::VALUE_REQUIRED, 'only notify games and reviews modified since this date', -1)
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'http request timeout in seconds', $this->requestTimeout);
    }

    public function __invoke(OutputInterface $output, InputInterface $input): int
    {
        if (empty($this->discordWebhookUrl)) {
            $output->writeln('The discord webhook URL is mandatory to use this command.');

            return Command::INVALID;
        }

        $since = intval($input->getOption('since'));
        if ($since < 0 || $since > time()) {
            $output->writeln('The --since option must be between 0 and the current timestamp.');

            return Command::INVALID;
        }

        $timeout = intval($input->getOption('timeout'));

        try {
            $output->writeln('Sending discord notification for new games and reviews since ' . date('Y-m-d H:i:s', $since) . '.');

            $output->write('Retriving games and reviews...');
            $dateTime = \DateTime::createFromTimestamp($since);
            $users = $this->userManager->findWithReview();
            $games = $this->gameRepo->findSince($dateTime, $this->discordWebhookLimit);
            $reviewsByUser = [];
            foreach ($users as $user) {
                $reviews = $this->reviewRepo->findSince($dateTime, $this->discordWebhookLimit, $user->getId());
                $reviewsByUser[$user->getId()]['users'] = $user;
                $reviewsByUser[$user->getId()]['reviews'] = array_slice($reviews, 0, $this->discordWebhookLimit); // remove on result as we have fetched one more that configured
                $reviewsByUser[$user->getId()]['hasMore'] = count($reviews) > $this->discordWebhookLimit; // determine if there is more games to fetch
            }
            $output->writeln(' Done.');

            $output->write('Generating markdown content...');
            $content = $this->twig->render('discord/notify.md.twig', [
                'games' => array_slice($games, 0, $this->discordWebhookLimit), // remove on result as we have fetched one more that configured
                'hasMore' => count($games) > $this->discordWebhookLimit, // determine if there is more games to fetch
                'reviewsByUser' => $reviewsByUser,
            ]);
            $output->writeln(' Done.');

            $output->write('Sending request to discord API...');
            $response = $this->client->request('POST', $this->discordWebhookUrl . '?wait=true', [
                'max_duration' => $timeout,
                'json' => ['content' => $content],
            ]);
            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Error when posting message through discord API.');
            }
            $output->writeln(' Done.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(' Failed.');
            if ($input->getOption('verbose')) {
                $output->write($e->getMessage());
            }

            return Command::FAILURE;
        }
    }
}
