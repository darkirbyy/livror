<?php

declare(strict_types=1);

namespace App\Entity\Main;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\UniqueConstraint(fields: ['id'])]
class Steam
{
    // /////////////////////////////////////////////////////
    // All fields and their validation constraints ////////
    // /////////////////////////////////////////////////////

    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __construct()
    {
    }

    // /////////////////////////////////////////////////////
    // Custom methods and validation constraints //////////
    // /////////////////////////////////////////////////////

    // /////////////////////////////////////////////////////
    // Doctrine auto-generated getter and setter //////////
    // /////////////////////////////////////////////////////

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
