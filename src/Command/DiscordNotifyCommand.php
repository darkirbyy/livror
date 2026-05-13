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
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DiscordNotifyCommand extends Command
{
    public function __construct(
        private string $locale,
        private int $requestTimeout,
        private string $discordWebhookUrl,
        private int $discordWebhookEllipsis,
        private UserManager $userManager,
        private GameRepository $gameRepo,
        private ReviewRepository $reviewRepo,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
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
            $usersInfo = $this->userManager->getUsersInfo();
            $games = $this->gameRepo->findSince($dateTime);
            $reviewsByUsers = [];
            foreach ($usersInfo as $userInfo) {
                $user = $userInfo->user;
                $reviewsByUsers[$user->uuid->toString()]['user'] = $user;
                $reviewsByUsers[$user->uuid->toString()]['reviews'] = $this->reviewRepo->findSince($dateTime, $user->uuid);
            }
            $output->writeln(' Done.');

            $output->write('Generating markdown content...');
            $this->translator->setLocale($this->locale);
            $content = $this->twig->render('discord/notify.md.twig', [
                'ellipsis' => $this->discordWebhookEllipsis,
                'games' => $games,
                'reviewsByUsers' => $reviewsByUsers,
                'url' => $this->urlGenerator->generate('home_index', [], UrlGenerator::ABSOLUTE_URL),
            ]);
            $output->writeln(' Done.');

            $output->write('Sending request to discord API...');
            $response = $this->client->request('POST', $this->discordWebhookUrl . '?wait=true', [
                'max_duration' => $timeout,
                'json' => ['content' => $content, 'flags' => 4096],
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
