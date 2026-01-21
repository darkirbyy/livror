<?php

declare(strict_types=1);

namespace App\Entity\Main;

use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File as FileMeta;
use Vich\UploaderBundle\Entity\File as VichFile;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Vich\UploaderBundle\Validator\Constraints as VichAssert;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[Vich\Uploadable]
class Attachment
{
    // /////////////////////////////////////////////////////
    // All fields and their validation constraints /////////
    // /////////////////////////////////////////////////////

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'attachments', fileNameProperty: 'fileMeta.name', size: 'fileMeta.size', mimeType: 'fileMeta.mimeType', originalName: 'fileMeta.originalName')]
    #[VichAssert\FileRequired(target: 'fileMeta')]
    #[Assert\AtLeastOneOf([new Assert\File(maxSize: '10Mi'), new Assert\Image(maxSize: '10Mi', detectCorrupted: true)])]
    private ?File $file = null;

    #[ORM\Embedded(class: VichFile::class)]
    private ?FileMeta $fileMeta = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $fileUpdatedAt = null;

    #[ORM\Column(length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Review $review = null;

    // /////////////////////////////////////////////////////
    // Custom methods and validation constraints ///////////
    // /////////////////////////////////////////////////////

    public function __construct()
    {
        $this->fileMeta = new FileMeta();
    }

    // /////////////////////////////////////////////////////
    // Doctrine auto-generated getter and setter ///////////
    // /////////////////////////////////////////////////////

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->fileUpdatedAt = new \DateTime();
        }
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFileMeta(FileMeta $fileMeta): void
    {
        $this->fileMeta = $fileMeta;
    }

    public function getFileMeta(): ?FileMeta
    {
        return $this->fileMeta;
    }

    public function getFileUpdatedAt(): ?\DateTime
    {
        return $this->fileUpdatedAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;

        return $this;
    }
}
