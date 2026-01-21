<?php

namespace App\Fixtures\Factory;

use App\Entity\Main\Attachment;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

final class AttachmentFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Attachment::class;
    }

    protected function defaults(): array
    {
        // Create a temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'upload-');
        file_put_contents($tempFile, self::faker()->text(1024));

        $defaults = [];
        $defaults['description'] = self::faker()->text(128);
        $defaults['file'] = new ReplacingFile($tempFile);

        return $defaults;
    }
}
