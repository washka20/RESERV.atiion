<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\ListUserMemberships;

use App\Modules\Identity\Application\DTO\MembershipWithOrgDTO;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Handler списка memberships пользователя с join на organizations.
 * Read-side без Eloquent (ADR-007). Используется AuthService для JWT claims.
 *
 * Возвращает только archived=false организации — archived orgs не должны
 * фигурировать в active context switching.
 */
final readonly class ListUserMembershipsHandler
{
    /**
     * @return list<MembershipWithOrgDTO>
     */
    public function handle(ListUserMembershipsQuery $query): array
    {
        $rows = DB::table('memberships as m')
            ->join('organizations as o', 'm.organization_id', '=', 'o.id')
            ->where('m.user_id', $query->userId)
            ->whereNull('o.archived_at')
            ->orderBy('m.created_at')
            ->select([
                'm.id as membership_id',
                'm.role as role',
                'o.id as organization_id',
                'o.slug as organization_slug',
            ])
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        return $items;
    }

    private function mapRow(stdClass $row): MembershipWithOrgDTO
    {
        return new MembershipWithOrgDTO(
            membershipId: (string) $row->membership_id,
            organizationId: (string) $row->organization_id,
            organizationSlug: (string) $row->organization_slug,
            role: (string) $row->role,
        );
    }
}
