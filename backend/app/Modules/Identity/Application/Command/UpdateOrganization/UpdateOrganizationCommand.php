<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\UpdateOrganization;

/**
 * Команда обновления профиля организации. Требует membership actor'а
 * в organization с permission settings.manage (owner/admin).
 */
final readonly class UpdateOrganizationCommand
{
    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>  $description
     */
    public function __construct(
        public string $organizationSlug,
        public string $actorUserId,
        public array $name,
        public array $description,
        public string $city,
        public ?string $district,
        public string $phone,
        public string $email,
    ) {}
}
