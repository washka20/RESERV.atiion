<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Controller;

use App\Modules\Identity\Application\Command\ChangeMembershipRole\ChangeMembershipRoleCommand;
use App\Modules\Identity\Application\Command\InviteMember\InviteMemberCommand;
use App\Modules\Identity\Application\Command\RevokeMembership\RevokeMembershipCommand;
use App\Modules\Identity\Application\DTO\MemberListItemDTO;
use App\Modules\Identity\Application\DTO\MembershipDTO;
use App\Modules\Identity\Application\Query\ListOrganizationMembers\ListOrganizationMembersQuery;
use App\Modules\Identity\Domain\Exception\LastOwnerCannotBeRevokedException;
use App\Modules\Identity\Domain\Exception\MembershipAlreadyExistsException;
use App\Modules\Identity\Domain\Exception\MembershipNotFoundException;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Exception\UserNotFoundException;
use App\Modules\Identity\Interface\Api\Request\ChangeRoleRequest;
use App\Modules\Identity\Interface\Api\Request\InviteMemberRequest;
use App\Modules\Identity\Interface\Api\Resource\MemberListItemResource;
use App\Modules\Identity\Interface\Api\Resource\MembershipResource;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Domain\Exception\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

/**
 * Управление members организации:
 * - GET /organizations/{slug}/members — team.view
 * - POST /organizations/{slug}/members/invite — team.manage
 * - DELETE /organizations/{slug}/members/{membershipId} — team.manage
 * - PATCH /organizations/{slug}/members/{membershipId}/role — team.manage
 */
final readonly class MembershipController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function index(string $slug, Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        try {
            /** @var list<MemberListItemDTO> $items */
            $items = $this->queryBus->ask(new ListOrganizationMembersQuery(
                organizationSlug: $slug,
                actorUserId: $userId,
            ));
        } catch (OrganizationNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        }

        return response()->json([
            'success' => true,
            'data' => MemberListItemResource::collection($items),
            'error' => null,
            'meta' => null,
        ]);
    }

    public function invite(string $slug, InviteMemberRequest $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $command = new InviteMemberCommand(
            organizationSlug: $slug,
            actorUserId: $userId,
            inviteeEmail: (string) $request->string('email'),
            role: (string) $request->string('role'),
        );

        try {
            /** @var MembershipDTO $dto */
            $dto = $this->commandBus->dispatch($command);
        } catch (OrganizationNotFoundException|UserNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (MembershipAlreadyExistsException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (OrganizationArchivedException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return $this->error('MEMBERSHIP_INVALID_ROLE', $e->getMessage(), 422);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        } catch (DomainException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 400);
        }

        return response()->json([
            'success' => true,
            'data' => new MembershipResource($dto),
            'error' => null,
            'meta' => null,
        ], 201);
    }

    public function revoke(string $slug, string $membershipId, Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        try {
            $this->commandBus->dispatch(new RevokeMembershipCommand(
                organizationSlug: $slug,
                actorUserId: $userId,
                targetMembershipId: $membershipId,
            ));
        } catch (OrganizationNotFoundException|MembershipNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (LastOwnerCannotBeRevokedException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        }

        return response()->json(null, 204);
    }

    public function changeRole(string $slug, string $membershipId, ChangeRoleRequest $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $command = new ChangeMembershipRoleCommand(
            organizationSlug: $slug,
            actorUserId: $userId,
            targetMembershipId: $membershipId,
            newRole: (string) $request->string('role'),
        );

        try {
            /** @var MembershipDTO $dto */
            $dto = $this->commandBus->dispatch($command);
        } catch (OrganizationNotFoundException|MembershipNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (LastOwnerCannotBeRevokedException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        }

        return response()->json([
            'success' => true,
            'data' => new MembershipResource($dto),
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

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => null,
            ],
            'meta' => null,
        ], $status);
    }
}
