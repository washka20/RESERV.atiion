<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Domain\ValueObject\UserId;

/**
 * Распарсенные JWT claims. Expose helper-методы для чтения memberships
 * claim (organization context), чтобы middleware / контроллеры не ковырялись
 * в raw-массиве.
 */
final readonly class ParsedClaims
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        public UserId $userId,
        public array $claims,
    ) {}

    /**
     * Возвращает список memberships из JWT. Каждый элемент — ассоц. массив
     * с ключами org_id, org_slug, role. Пустой массив если в токене нет claim.
     *
     * @return list<array{org_id: string, org_slug: string, role: string}>
     */
    public function memberships(): array
    {
        $raw = $this->claims['memberships'] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $orgId = (string) ($item['org_id'] ?? '');
            $orgSlug = (string) ($item['org_slug'] ?? '');
            $role = (string) ($item['role'] ?? '');
            if ($orgId === '' || $orgSlug === '' || $role === '') {
                continue;
            }
            $out[] = [
                'org_id' => $orgId,
                'org_slug' => $orgSlug,
                'role' => $role,
            ];
        }

        return $out;
    }
}
