<?php

namespace App\Fixtures\Story\Command;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Story;

final class DeleteReviewsStory extends Story
{
    public function __construct(private ManagerRegistry $managerRegistry) {}

    public function build(): void
    {
        $connectedUserUuid = TestStory::get('connected-user-uuid');

        // Create 10 games only reviewed by user 1 (=connected) with attachments
        GameFactory::new()
            ->withUsersUuid([$connectedUserUuid], true, 'forced')
            ->many(5)
            ->create();
    }
}
