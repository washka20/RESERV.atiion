<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entity;

use App\Modules\Catalog\Domain\Event\CategoryCreated;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Category aggregate root. Catalog BC.
 *
 * Содержит коллекцию Subcategory как child entities.
 * Восстановление из БД — через Category::restore() без генерации событий.
 */
final class Category extends AggregateRoot
{
    /**
     * @param  list<Subcategory>  $subcategories
     */
    private function __construct(
        private readonly CategoryId $id,
        private string $name,
        private readonly string $slug,
        private int $sortOrder,
        private array $subcategories,
    ) {}

    /**
     * Создаёт новую категорию и записывает событие CategoryCreated.
     */
    public static function create(CategoryId $id, string $name, string $slug, int $sortOrder): self
    {
        $category = new self($id, $name, $slug, $sortOrder, []);
        $category->recordEvent(new CategoryCreated($id, $slug, new DateTimeImmutable));

        return $category;
    }

    /**
     * Восстанавливает категорию из хранилища без генерации domain events.
     *
     * @param  list<Subcategory>  $subcategories
     */
    public static function restore(
        CategoryId $id,
        string $name,
        string $slug,
        int $sortOrder,
        array $subcategories,
    ): self {
        return new self($id, $name, $slug, $sortOrder, $subcategories);
    }

    /**
     * Добавляет подкатегорию в категорию.
     *
     * @throws InvalidArgumentException если подкатегория с таким id уже существует
     */
    public function addSubcategory(Subcategory $subcategory): void
    {
        foreach ($this->subcategories as $existing) {
            if ($existing->id()->equals($subcategory->id())) {
                throw new InvalidArgumentException(
                    sprintf('Subcategory with id "%s" already exists', $subcategory->id()->toString())
                );
            }
        }

        $this->subcategories[] = $subcategory;
    }

    /**
     * Удаляет подкатегорию по идентификатору. Идемпотентно.
     */
    public function removeSubcategory(SubcategoryId $id): void
    {
        foreach ($this->subcategories as $i => $existing) {
            if ($existing->id()->equals($id)) {
                unset($this->subcategories[$i]);
                $this->subcategories = array_values($this->subcategories);

                return;
            }
        }
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function id(): CategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @return list<Subcategory>
     */
    public function subcategories(): array
    {
        return $this->subcategories;
    }
}
