<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Main\Attachment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Vich\UploaderBundle\Handler\DownloadHandler;

#[Route('/attachment', name: 'attachment_')]
class AttachmentController extends AbstractController
{
    // Download an attachment
    #[Route('/{id}', name: 'download', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    public function download(Attachment $attachment, DownloadHandler $downloadHandler): Response
    {
        return $downloadHandler->downloadObject($attachment, 'file');
    }
}
