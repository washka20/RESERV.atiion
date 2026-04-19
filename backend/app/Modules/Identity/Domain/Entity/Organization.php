<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entity;

use App\Modules\Identity\Domain\Event\OrganizationArchived;
use App\Modules\Identity\Domain\Event\OrganizationCreated;
use App\Modules\Identity\Domain\Event\OrganizationVerified;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
use App\Modules\Identity\Domain\ValueObject\CancellationPolicy;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Организация — aggregate root Identity BC.
 *
 * Хранит бизнес-профиль (имя, контакты, локация), KYC-флаг, политику отмены
 * и материализованные reputation-поля (rating, reviews_count — обновляются
 * listener'ами другого BC). Lifecycle: create -> (optional archive/verify)
 * -> updateDetails. Archived организация read-only для доменных операций,
 * но bookings/services остаются доступны для истории.
 */
final class Organization extends AggregateRoot
{
    private const MIN_PHONE_LENGTH = 7;

    /**
     * @param  array<string, string>  $name  locale => translation, обязателен ключ 'ru'
     * @param  array<string, string>  $description  locale => translation, допустим пустой массив
     */
    private function __construct(
        public readonly OrganizationId $id,
        public readonly OrganizationSlug $slug,
        private array $name,
        private array $description,
        public readonly OrganizationType $type,
        private ?string $logoUrl,
        private string $city,
        private ?string $district,
        private string $phone,
        private string $email,
        private bool $verified,
        private CancellationPolicy $cancellationPolicy,
        private float $rating,
        private int $reviewsCount,
        private ?DateTimeImmutable $archivedAt,
        public readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Создаёт новую Organization с дефолтами (verified=false, policy=FLEXIBLE, rating=0)
     * и записывает OrganizationCreated event.
     *
     * @param  array<string, string>  $name  минимум ключ 'ru' с непустым значением
     * @param  array<string, string>  $description  пустой массив допустим; если не пустой — нужен ключ 'ru'
     *
     * @throws InvalidArgumentException при нарушении инвариантов (empty name.ru, invalid email, короткий phone)
     */
    public static function create(
        OrganizationId $id,
        OrganizationSlug $slug,
        array $name,
        array $description,
        OrganizationType $type,
        string $city,
        string $phone,
        string $email,
    ): self {
        self::assertNameValid($name);
        self::assertDescriptionValid($description);
        self::assertPhoneValid($phone);
        self::assertEmailValid($email);

        $now = new DateTimeImmutable;
        $organization = new self(
            id: $id,
            slug: $slug,
            name: $name,
            description: $description,
            type: $type,
            logoUrl: null,
            city: $city,
            district: null,
            phone: $phone,
            email: $email,
            verified: false,
            cancellationPolicy: CancellationPolicy::FLEXIBLE,
            rating: 0.0,
            reviewsCount: 0,
            archivedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
        $organization->recordEvent(new OrganizationCreated($id, $slug, $type, $now));

        return $organization;
    }

    /**
     * Восстанавливает Organization из persistence без записи domain events.
     * Используется mapper'ом репозитория.
     *
     * @param  array<string, string>  $name
     * @param  array<string, string>  $description
     */
    public static function reconstitute(
        OrganizationId $id,
        OrganizationSlug $slug,
        array $name,
        array $description,
        OrganizationType $type,
        ?string $logoUrl,
        string $city,
        ?string $district,
        string $phone,
        string $email,
        bool $verified,
        CancellationPolicy $cancellationPolicy,
        float $rating,
        int $reviewsCount,
        ?DateTimeImmutable $archivedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $slug,
            $name,
            $description,
            $type,
            $logoUrl,
            $city,
            $district,
            $phone,
            $email,
            $verified,
            $cancellationPolicy,
            $rating,
            $reviewsCount,
            $archivedAt,
            $createdAt,
            $updatedAt,
        );
    }

    /**
     * Архивирует организацию — скрывает из marketplace. Не идемпотентно:
     * повторный archive() бросает OrganizationArchivedException.
     *
     * @throws OrganizationArchivedException если уже archived
     */
    public function archive(): void
    {
        if ($this->archivedAt !== null) {
            throw OrganizationArchivedException::forId($this->id);
        }

        $now = new DateTimeImmutable;
        $this->archivedAt = $now;
        $this->updatedAt = $now;
        $this->recordEvent(new OrganizationArchived($this->id, $now));
    }

    /**
     * Помечает организацию как прошедшую KYC. Идемпотентно: повторный verify()
     * не эмитит event и не меняет updatedAt.
     */
    public function verify(): void
    {
        if ($this->verified) {
            return;
        }

        $now = new DateTimeImmutable;
        $this->verified = true;
        $this->updatedAt = $now;
        $this->recordEvent(new OrganizationVerified($this->id, $now));
    }

    /**
     * Обновляет публичные данные организации. Бросает исключение, если организация
     * уже archived — archived запись read-only.
     *
     * @param  array<string, string>  $name
     * @param  array<string, string>  $description
     *
     * @throws OrganizationArchivedException
     * @throws InvalidArgumentException
     */
    public function updateDetails(
        array $name,
        array $description,
        string $city,
        ?string $district,
        string $phone,
        string $email,
    ): void {
        if ($this->archivedAt !== null) {
            throw OrganizationArchivedException::forId($this->id);
        }

        self::assertNameValid($name);
        self::assertDescriptionValid($description);
        self::assertPhoneValid($phone);
        self::assertEmailValid($email);

        $this->name = $name;
        $this->description = $description;
        $this->city = $city;
        $this->district = $district;
        $this->phone = $phone;
        $this->email = $email;
        $this->updatedAt = new DateTimeImmutable;
    }

    /**
     * Устанавливает URL логотипа. Null очищает значение.
     */
    public function setLogo(?string $logoUrl): void
    {
        $this->logoUrl = $logoUrl;
        $this->updatedAt = new DateTimeImmutable;
    }

    /**
     * Меняет политику отмены бронирования (применяется ко всем новым bookings).
     */
    public function changeCancellationPolicy(CancellationPolicy $policy): void
    {
        $this->cancellationPolicy = $policy;
        $this->updatedAt = new DateTimeImmutable;
    }

    /**
     * Возвращает имя в указанной локали; если ключа нет — fallback на ru.
     */
    public function name(string $locale = 'ru'): string
    {
        return $this->name[$locale] ?? $this->name['ru'];
    }

    /**
     * Возвращает описание в указанной локали; если ключа нет — fallback на ru
     * (или пустая строка, если описание вовсе не задано).
     */
    public function description(string $locale = 'ru'): string
    {
        return $this->description[$locale] ?? $this->description['ru'] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function nameTranslations(): array
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function descriptionTranslations(): array
    {
        return $this->description;
    }

    public function logoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function district(): ?string
    {
        return $this->district;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function cancellationPolicy(): CancellationPolicy
    {
        return $this->cancellationPolicy;
    }

    public function rating(): float
    {
        return $this->rating;
    }

    public function reviewsCount(): int
    {
        return $this->reviewsCount;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param  array<string, string>  $name
     */
    private static function assertNameValid(array $name): void
    {
        if (! isset($name['ru']) || trim($name['ru']) === '') {
            throw new InvalidArgumentException('Organization name must contain a non-empty "ru" translation');
        }
    }

    /**
     * @param  array<string, string>  $description
     */
    private static function assertDescriptionValid(array $description): void
    {
        if ($description === []) {
            return;
        }

        if (! array_key_exists('ru', $description)) {
            throw new InvalidArgumentException('Organization description must contain a "ru" key when provided');
        }
    }

    private static function assertPhoneValid(string $phone): void
    {
        if (strlen(trim($phone)) < self::MIN_PHONE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Organization phone must be at least %d characters long',
                self::MIN_PHONE_LENGTH,
            ));
        }
    }

    private static function assertEmailValid(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('Organization email "%s" is not a valid address', $email));
        }
    }
}
