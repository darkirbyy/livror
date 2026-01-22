<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Fixtures\Factory\AttachmentFactory;
use App\Fixtures\Story\Attachment\AttachmentDownloadStory;
use PHPUnit\Framework\Attributes as PU;

class AttachmentControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    public function download(): void
    {
        AttachmentDownloadStory::load();

        $attachment = AttachmentFactory::repository()->random([]);
        $this->client->request('GET', '/attachment/' . $attachment->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('content-disposition');
    }
}
