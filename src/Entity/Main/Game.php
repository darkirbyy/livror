<?php

declare(strict_types=1);

namespace App\Entity\Main;

use App\Enum\TypeGameEnum;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'])]
#[UniqueEntity(fields: ['steamId'])]
#[ORM\UniqueConstraint(fields: ['name'])]
#[ORM\UniqueConstraint(fields: ['steamId'])]
class Game
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

    #[ORM\Column(nullable: true)]
    #[Assert\Regex('/^\d+$/', message: 'game.error.steamId.invalid')]
    private ?int $steamId = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 2)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(enumType: TypeGameEnum::class)]
    #[Assert\Type(TypeGameEnum::class)]
    #[Assert\NotBlank]
    private ?TypeGameEnum $typeGame = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $developers = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1900, max: 2100)]
    private ?int $releaseYear = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $fullPrice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genres = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url(requireTld: true)]
    private ?string $imgUrl = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'game', orphanRemoval: true)]
    #[ORM\OrderBy(['dateAdd' => 'ASC'])]
    private Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->typeGame = TypeGameEnum::GAME;
    }

    // /////////////////////////////////////////////////////
    // Custom methods and validation constraints //////////
    // /////////////////////////////////////////////////////

    // Custom callback to check that the image URL is a valid one
    #[Assert\Callback]
    public function validateImageUrl(ExecutionContextInterface $context): void
    {
        // No checks if the field if empty
        if (empty($this->imgUrl)) {
            return;
        }

        // Check image : valid extensions
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $cleanUrl = strtok($this->imgUrl, '?');
        $extension = strtolower(pathinfo($cleanUrl, PATHINFO_EXTENSION));
        if (!in_array($extension, $validExtensions, true)) {
            $context->buildViolation('game.error.imgUrl.notValidExtension')->atPath('imgUrl')->addViolation();

            return;
        }

        // Check image : is reachable, good status code, no redirect and good Content-Type
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request('HEAD', $this->imgUrl, [
                'timeout' => $_ENV['APP_REQUEST_TIMEOUT'],
            ]);

            $statusCode = $response->getStatusCode();
            $redirectCount = $response->getInfo('redirect_count');
            $contentType = $response->getHeaders()['content-type'][0] ?? null;
            if (200 !== $statusCode || $redirectCount > 0 || !str_starts_with($contentType, 'image/')) {
                $context->buildViolation('game.error.imgUrl.notAnImage')->atPath('imgUrl')->addViolation();
            }
        } catch (\Exception $e) {
            $context->buildViolation('game.error.imgUrl.notReachable')->atPath('imgUrl')->addViolation();
        }
    }

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

    public function getSteamId(): ?int
    {
        return $this->steamId;
    }

    public function setSteamId(?int $steamId): static
    {
        $this->steamId = $steamId;

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

    public function getTypeGame(): ?TypeGameEnum
    {
        return $this->typeGame;
    }

    public function setTypeGame(?TypeGameEnum $typeGame): static
    {
        $this->typeGame = $typeGame;

        return $this;
    }

    public function getDevelopers(): ?string
    {
        return $this->developers;
    }

    public function setDevelopers(?string $developers): static
    {
        $this->developers = $developers;

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(?int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function getFullPrice(): ?int
    {
        return $this->fullPrice;
    }

    public function setFullPrice(?int $fullPrice): static
    {
        $this->fullPrice = $fullPrice;

        return $this;
    }

    public function getGenres(): ?string
    {
        return $this->genres;
    }

    public function setGenres(?string $genres): static
    {
        $this->genres = $genres;

        return $this;
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

    public function getImgUrl(): ?string
    {
        return $this->imgUrl;
    }

    public function setImgUrl(?string $imgUrl): static
    {
        $this->imgUrl = $imgUrl;

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setGame($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getGame() === $this) {
                $review->setGame(null);
            }
        }

        return $this;
    }
}
