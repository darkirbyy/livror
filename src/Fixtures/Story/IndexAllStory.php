<?php

namespace App\Fixtures\Story;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Story;

final class IndexAllStory extends Story
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function build(): void
    {
        // Fetch the users id available through the account connection
        $userRepository = $this->managerRegistry->getManager('account')->getRepository(User::class);
        $usersId = array_map(fn (User $u) => $u->getId(), $userRepository->findAll());

        // Create 20 games with "0" to "number of users" reviews
        GameFactory::new()->withUsersId($usersId, false)->many(20)->create();
    }
}
