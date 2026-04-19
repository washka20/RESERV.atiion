<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\CreateOrganization;

/**
 * Команда создания организации. Создатель автоматически становится owner.
 *
 * Slug генерируется автоматически из name['ru'] через SlugGenerator.
 */
final readonly class CreateOrganizationCommand
{
    /**
     * @param  array<string, string>  $name  ['ru' => '...', 'en' => '...'] — обязателен ключ 'ru'
     * @param  array<string, string>  $description  пустой массив допустим
     */
    public function __construct(
        public string $userId,
        public array $name,
        public array $description,
        public string $type,
        public string $city,
        public string $phone,
        public string $email,
    ) {}
}
