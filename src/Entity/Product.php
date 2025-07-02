<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_product_brand', columns: ['brand'])]
#[ORM\Index(name: 'idx_product_name', columns: ['name'])]
#[OA\Schema(
    title: 'Product',
    description: 'Téléphone mobile BileMo',
    type: 'object'
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'product:list'])]
    #[OA\Property(
        description: 'Identifiant unique du produit',
        example: 1
    )]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['product:read', 'product:list'])]
    #[OA\Property(
        description: 'Nom commercial du téléphone',
        example: 'iPhone 15 Pro Max'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La marque est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'La marque doit contenir au moins {{ limit }} caractères'
    )]
    #[Groups(['product:read', 'product:list'])]
    #[OA\Property(
        description: 'Marque du téléphone',
        example: 'Apple'
    )]
    private ?string $brand = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le modèle est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le modèle doit contenir au moins {{ limit }} caractères'
    )]
    #[Groups(['product:read', 'product:list'])]
    #[OA\Property(
        description: 'Modèle spécifique',
        example: 'A2849'
    )]
    private ?string $model = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul')]
    #[Assert\LessThan(
        value: 100000,
        message: 'Le prix ne peut pas dépasser {{ compared_value }}€'
    )]
    #[Groups(['product:read', 'product:list'])]
    #[OA\Property(
        description: 'Prix en euros',
        example: '1199.99'
    )]
    private ?string $price = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['product:read'])]
    #[OA\Property(
        description: 'Description détaillée du produit',
        example: 'Le téléphone le plus avancé avec...'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['product:read'])]
    #[OA\Property(
        description: 'Spécifications techniques',
        type: 'object',
        example: ['screen' => '6.7 pouces', 'storage' => '256 GB', 'camera' => '48 MP']
    )]
    private array $specifications = [];

    #[ORM\Column]
    #[Groups(['product:read'])]
    #[OA\Property(
        description: 'Date de création',
        type: 'string',
        format: 'date-time',
        example: '2024-01-15T10:30:00+00:00'
    )]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    #[OA\Property(
        description: 'Date de dernière mise à jour',
        type: 'string',
        format: 'date-time',
        example: '2024-01-20T14:45:00+00:00'
    )]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    #[Groups(['product:read', 'product:list'])]
    #[OA\Property(
        description: 'Prix formaté avec devise',
        example: '1 199,99 €'
    )]
    public function getFormattedPrice(): string
    {
        return number_format((float) $this->price, 2, ',', ' ') . ' €';
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

    public function getSpecifications(): array
    {
        return $this->specifications;
    }

    public function setSpecifications(?array $specifications): static
    {
        $this->specifications = $specifications ?? [];
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->brand . ' ' . $this->name ?? '';
    }
}
