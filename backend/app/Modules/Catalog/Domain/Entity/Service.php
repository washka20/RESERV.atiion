<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entity;

use App\Modules\Catalog\Domain\Event\ServiceActivated;
use App\Modules\Catalog\Domain\Event\ServiceCreated;
use App\Modules\Catalog\Domain\Event\ServiceDeactivated;
use App\Modules\Catalog\Domain\Event\ServiceUpdated;
use App\Modules\Catalog\Domain\Exception\InvalidServiceTypeException;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

/**
 * Service aggregate root. Catalog BC.
 *
 * Два типа услуг:
 * - TIME_SLOT: требует duration (длительность одного слота)
 * - QUANTITY:  требует totalQuantity > 0 (кол-во единиц на диапазон дат)
 *
 * Инварианты проверяются в фабричных методах createTimeSlot() / createQuantity().
 * Восстановление из БД — через Service::restore() без генерации событий.
 */
final class Service extends AggregateRoot
{
    /**
     * @param  list<ImagePath>  $images
     */
    private function __construct(
        private readonly ServiceId $id,
        private string $name,
        private string $description,
        private Money $price,
        private readonly ServiceType $type,
        private readonly ?Duration $duration,
        private readonly ?int $totalQuantity,
        private readonly CategoryId $categoryId,
        private readonly ?SubcategoryId $subcategoryId,
        private bool $isActive,
        private array $images,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Создаёт услугу типа TIME_SLOT. Требует duration.
     *
     * @throws InvalidServiceTypeException если duration null
     */
    public static function createTimeSlot(
        ServiceId $id,
        string $name,
        string $description,
        Money $price,
        Duration $duration,
        CategoryId $categoryId,
        ?SubcategoryId $subcategoryId,
    ): self {
        $now = new DateTimeImmutable;
        $service = new self(
            $id,
            $name,
            $description,
            $price,
            ServiceType::TIME_SLOT,
            $duration,
            null,
            $categoryId,
            $subcategoryId,
            true,
            [],
            $now,
            $now,
        );
        $service->recordEvent(new ServiceCreated($id, $categoryId, ServiceType::TIME_SLOT, $now));

        return $service;
    }

    /**
     * Создаёт услугу типа QUANTITY. Требует totalQuantity > 0.
     *
     * @throws InvalidServiceTypeException если totalQuantity <= 0
     */
    public static function createQuantity(
        ServiceId $id,
        string $name,
        string $description,
        Money $price,
        int $totalQuantity,
        CategoryId $categoryId,
        ?SubcategoryId $subcategoryId,
    ): self {
        if ($totalQuantity <= 0) {
            throw InvalidServiceTypeException::missingQuantity();
        }

        $now = new DateTimeImmutable;
        $service = new self(
            $id,
            $name,
            $description,
            $price,
            ServiceType::QUANTITY,
            null,
            $totalQuantity,
            $categoryId,
            $subcategoryId,
            true,
            [],
            $now,
            $now,
        );
        $service->recordEvent(new ServiceCreated($id, $categoryId, ServiceType::QUANTITY, $now));

        return $service;
    }

    /**
     * Восстанавливает услугу из хранилища без генерации domain events.
     *
     * @param  list<ImagePath>  $images
     *
     * @throws InvalidServiceTypeException при нарушении инварианта type + duration/totalQuantity
     */
    public static function restore(
        ServiceId $id,
        string $name,
        string $description,
        Money $price,
        ServiceType $type,
        ?Duration $duration,
        ?int $totalQuantity,
        CategoryId $categoryId,
        ?SubcategoryId $subcategoryId,
        bool $isActive,
        array $images,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        if ($type === ServiceType::TIME_SLOT && $duration === null) {
            throw InvalidServiceTypeException::missingDuration();
        }
        if ($type === ServiceType::QUANTITY && ($totalQuantity === null || $totalQuantity <= 0)) {
            throw InvalidServiceTypeException::missingQuantity();
        }

        return new self(
            $id,
            $name,
            $description,
            $price,
            $type,
            $duration,
            $totalQuantity,
            $categoryId,
            $subcategoryId,
            $isActive,
            $images,
            $createdAt,
            $updatedAt,
        );
    }

    /**
     * Обновляет name/description/price и помечает updatedAt.
     */
    public function updateDetails(string $name, string $description, Money $price): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new ServiceUpdated($this->id, $this->updatedAt));
    }

    /**
     * Деактивирует услугу. Идемпотентно: повторный вызов не пишет событие.
     */
    public function deactivate(): void
    {
        if (! $this->isActive) {
            return;
        }

        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new ServiceDeactivated($this->id, $this->updatedAt));
    }

    /**
     * Активирует услугу. Идемпотентно: повторный вызов не пишет событие.
     */
    public function activate(): void
    {
        if ($this->isActive) {
            return;
        }

        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new ServiceActivated($this->id, $this->updatedAt));
    }

    /**
     * Добавляет изображение. Идемпотентно: дубликаты по value игнорируются.
     */
    public function addImage(ImagePath $path): void
    {
        foreach ($this->images as $existing) {
            if ($existing->equals($path)) {
                return;
            }
        }

        $this->images[] = $path;
        $this->updatedAt = new DateTimeImmutable;
    }

    /**
     * Удаляет изображение по пути. Идемпотентно.
     */
    public function removeImage(ImagePath $path): void
    {
        foreach ($this->images as $i => $existing) {
            if ($existing->equals($path)) {
                unset($this->images[$i]);
                $this->images = array_values($this->images);
                $this->updatedAt = new DateTimeImmutable;

                return;
            }
        }
    }

    public function id(): ServiceId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function type(): ServiceType
    {
        return $this->type;
    }

    public function duration(): ?Duration
    {
        return $this->duration;
    }

    public function totalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function subcategoryId(): ?SubcategoryId
    {
        return $this->subcategoryId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return list<ImagePath>
     */
    public function images(): array
    {
        return $this->images;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
