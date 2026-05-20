<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Uid\Uuid;

class DeleteReviewsCommand extends Command
{
    public function __construct(private ReviewRepository $reviewRepo, private EntityManagerInterface $entityManager)
    {
        parent::__construct('app:delete:reviews');
    }

    protected function configure(): void
    {
        $this->setDescription('Delete all reviews and attachments written by a given user.')->addArgument('userUuid', InputArgument::REQUIRED, 'the keycloak UUID of the user');
    }

    public function __invoke(OutputInterface $output, InputInterface $input): int
    {
        $userUuid = $input->getArgument('userUuid');

        if (empty($userUuid) || !Uuid::isValid($userUuid)) {
            $output->writeln('The provided user UUID is not valid.');

            return Command::INVALID;
        }

        $userUuid = Uuid::fromString($userUuid);
        $reviews = $this->reviewRepo->findDelete($userUuid);
        if (0 === count($reviews)) {
            $output->writeln('This user has not written any reviews.');

            return Command::SUCCESS;
        }

        $helper = new QuestionHelper();
        $question = new ConfirmationQuestion(
            'This user has written ' . count($reviews) . ' review(s). They will be deleted permanently, along with their attachments. Continue with this action ? [y/N]',
            false,
        );
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {
            foreach ($reviews as $review) {
                $this->entityManager->remove($review);
            }
            $this->entityManager->flush();
            $output->writeln('Done.');

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
