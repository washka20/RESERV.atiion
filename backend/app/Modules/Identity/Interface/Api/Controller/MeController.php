<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Controller;

use App\Modules\Identity\Application\DTO\MembershipWithOrgDTO;
use App\Modules\Identity\Application\Query\ListUserMemberships\ListUserMembershipsQuery;
use App\Modules\Identity\Interface\Api\Resource\MembershipWithOrgResource;
use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Endpoints для информации о текущем пользователе (пост-auth).
 * GET /me/memberships — список orgs где user является member.
 */
final readonly class MeController
{
    public function __construct(private QueryBusInterface $queryBus) {}

    public function memberships(Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        /** @var list<MembershipWithOrgDTO> $items */
        $items = $this->queryBus->ask(new ListUserMembershipsQuery($userId));

        return response()->json([
            'success' => true,
            'data' => MembershipWithOrgResource::collection($items),
            'error' => null,
            'meta' => null,
        ]);
    }

    private function actorUserId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            throw new RuntimeException('Unauthorized actor');
        }

        return (string) $user->getAuthIdentifier();
    }
}
