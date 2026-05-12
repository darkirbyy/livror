<?php

namespace App\Fixtures\Story\Attachment;

use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class AttachmentDownloadStory extends Story
{
    public function build(): void
    {
        $allUsersUuid = TestStory::getPool('all-users-uuid');
        GameFactory::new()->withUsersUuid($allUsersUuid, true, 'forced')->many(5)->create();
    }
}
