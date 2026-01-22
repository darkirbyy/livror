<?php

namespace App\Fixtures\Story\Steam;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class AttachmentDownloadStory extends Story
{
    public function build(): void
    {
        $usersId = array_map(fn(User $u) => $u->getId(), UserFactory::all());
        GameFactory::new()->withUsersId($usersId, true, 'forced')->many(5)->create();
    }
}
