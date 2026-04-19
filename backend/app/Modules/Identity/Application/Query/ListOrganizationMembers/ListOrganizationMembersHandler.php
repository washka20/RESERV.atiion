<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\ListOrganizationMembers;

use App\Modules\Identity\Application\DTO\MemberListItemDTO;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;

/**
 * Handler списка members организации. Read-side без Eloquent (ADR-007).
 *
 * Authorization: actor должен быть member'ом с permission team.view
 * (owner/admin/staff — viewer не имеет team.view).
 */
final readonly class ListOrganizationMembersHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
    ) {}

    /**
     * @return list<MemberListItemDTO>
     */
    public function handle(ListOrganizationMembersQuery $query): array
    {
        $slug = new OrganizationSlug($query->organizationSlug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        $actorId = new UserId($query->actorUserId);
        $actorMembership = $this->memberships->findByPair($actorId, $organization->id);
        if ($actorMembership === null || ! $actorMembership->role()->can('team.view')) {
            throw new RuntimeException('Forbidden: insufficient permissions to list members');
        }

        $rows = DB::table('memberships as m')
            ->join('users as u', 'm.user_id', '=', 'u.id')
            ->where('m.organization_id', $organization->id->toString())
            ->orderBy('m.created_at')
            ->select([
                'm.id as membership_id',
                'u.id as user_id',
                'u.email as user_email',
                'u.first_name as user_first_name',
                'u.last_name as user_last_name',
                'm.role as role',
                'm.accepted_at as accepted_at',
                'm.created_at as created_at',
            ])
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        return $items;
    }

    private function mapRow(stdClass $row): MemberListItemDTO
    {
        return new MemberListItemDTO(
            membershipId: (string) $row->membership_id,
            userId: (string) $row->user_id,
            userEmail: (string) $row->user_email,
            userFirstName: (string) $row->user_first_name,
            userLastName: (string) $row->user_last_name,
            role: (string) $row->role,
            acceptedAt: $this->toIsoOrNull($row->accepted_at ?? null),
            joinedAt: $this->toIsoOrFallback($row->created_at),
        );
    }

    private function toIsoOrNull(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (new DateTimeImmutable((string) $raw))->format(DATE_ATOM);
    }

    private function toIsoOrFallback(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return (new DateTimeImmutable)->format(DATE_ATOM);
        }

        return (new DateTimeImmutable((string) $raw))->format(DATE_ATOM);
    }
}
