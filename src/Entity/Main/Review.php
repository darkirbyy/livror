<?php

declare(strict_types=1);

namespace App\Entity\Main;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['userId', 'game'])]
#[ORM\UniqueConstraint(fields: ['userId', 'game'])]
class Review
{
    // /////////////////////////////////////////////////////
    // All fields and their validation constraints ////////
    // /////////////////////////////////////////////////////

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAdd = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateUpdate = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\Range(min: '-100 years', max: '+100 years')]
    private ?\DateTimeInterface $firstPlay = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $hourSpend = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 6)]
    private ?int $mark = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?int $userId = null;

    // /////////////////////////////////////////////////////
    // Custom methods and validation constraints //////////
    // /////////////////////////////////////////////////////

    // Auto fill "dateAdd" and "dateUpdate" date when storing the entity to the database
    #[ORM\PrePersist]
    public function onPrePersit(): void
    {
        $this->dateAdd = new \DateTimeImmutable();
        $this->onPreUpdate();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateUpdate = new \DateTimeImmutable();
    }

    // /////////////////////////////////////////////////////
    // Doctrine auto-generated getter and setter //////////
    // /////////////////////////////////////////////////////

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAdd(): ?\DateTimeInterface
    {
        return $this->dateAdd;
    }

    public function setDateAdd(\DateTimeInterface $dateAdd): static
    {
        $this->dateAdd = $dateAdd;

        return $this;
    }

    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    public function setDateUpdate(\DateTimeInterface $dateUpdate): static
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getFirstPlay(): ?string
    {
        return $this->firstPlay;
    }

    public function setFirstPlay(?string $firstPlay): static
    {
        $this->firstPlay = $firstPlay;

        return $this;
    }

    public function getHourSpend(): ?int
    {
        return $this->hourSpend;
    }

    public function setHourSpend(?int $hourSpend): static
    {
        $this->hourSpend = $hourSpend;

        return $this;
    }

    public function getMark(): ?int
    {
        return $this->mark;
    }

    public function setMark(?int $mark): static
    {
        $this->mark = $mark;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }
}
