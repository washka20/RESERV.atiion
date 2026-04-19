<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Controller;

use App\Modules\Identity\Application\Command\ArchiveOrganization\ArchiveOrganizationCommand;
use App\Modules\Identity\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Modules\Identity\Application\Command\UpdateOrganization\UpdateOrganizationCommand;
use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Application\Query\GetOrganizationBySlug\GetOrganizationBySlugQuery;
use App\Modules\Identity\Domain\Exception\DuplicateSlugException;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Interface\Api\Request\CreateOrganizationRequest;
use App\Modules\Identity\Interface\Api\Request\UpdateOrganizationRequest;
use App\Modules\Identity\Interface\Api\Resource\OrganizationResource;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Domain\Exception\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Публичные endpoints управления организациями:
 * - GET /organizations/{slug} — профиль (любой авторизованный)
 * - POST /organizations — создать (текущий user становится owner)
 * - PATCH /organizations/{slug} — settings.manage
 * - DELETE /organizations/{slug} — organization.archive (owner only)
 *
 * Permission gates dual: middleware (primary) + handler (defense in depth).
 */
final readonly class OrganizationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function show(string $slug): JsonResponse
    {
        try {
            /** @var OrganizationDTO $dto */
            $dto = $this->queryBus->ask(new GetOrganizationBySlugQuery($slug));
        } catch (OrganizationNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        }

        return $this->envelope(new OrganizationResource($dto));
    }

    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        /** @var array{ru: string, en?: string} $name */
        $name = $request->input('name', []);
        /** @var array<string, string> $description */
        $description = $request->input('description', []);

        $command = new CreateOrganizationCommand(
            userId: $userId,
            name: $name,
            description: $description,
            type: (string) $request->string('type'),
            city: (string) $request->string('city'),
            phone: (string) $request->string('phone'),
            email: (string) $request->string('email'),
        );

        try {
            /** @var OrganizationDTO $dto */
            $dto = $this->commandBus->dispatch($command);
        } catch (DuplicateSlugException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (DomainException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 400);
        }

        return $this->envelope(new OrganizationResource($dto), status: 201);
    }

    public function update(string $slug, UpdateOrganizationRequest $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        // Fetch current state to fill defaults — handler требует полный набор полей.
        try {
            /** @var OrganizationDTO $current */
            $current = $this->queryBus->ask(new GetOrganizationBySlugQuery($slug));
        } catch (OrganizationNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        }

        /** @var array<string, string> $name */
        $name = $request->has('name') ? $request->input('name', []) : $current->name;
        /** @var array<string, string> $description */
        $description = $request->has('description') ? $request->input('description', []) : $current->description;

        $command = new UpdateOrganizationCommand(
            organizationSlug: $slug,
            actorUserId: $userId,
            name: $name,
            description: $description,
            city: (string) ($request->has('city') ? $request->string('city') : $current->city),
            district: $request->has('district') ? $this->nullableString($request->input('district')) : $current->district,
            phone: (string) ($request->has('phone') ? $request->string('phone') : $current->phone),
            email: (string) ($request->has('email') ? $request->string('email') : $current->email),
        );

        try {
            /** @var OrganizationDTO $dto */
            $dto = $this->commandBus->dispatch($command);
        } catch (OrganizationNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (OrganizationArchivedException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        } catch (DomainException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 400);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Forbidden')) {
                return $this->error('FORBIDDEN', 'Forbidden', 403);
            }
            throw $e;
        }

        return $this->envelope(new OrganizationResource($dto));
    }

    public function archive(string $slug, Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        try {
            $this->commandBus->dispatch(new ArchiveOrganizationCommand(
                organizationSlug: $slug,
                actorUserId: $userId,
            ));
        } catch (OrganizationNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (OrganizationArchivedException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        }

        return response()->json(null, 204);
    }

    private function actorUserId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            throw new RuntimeException('Unauthorized actor');
        }

        return (string) $user->getAuthIdentifier();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function envelope(mixed $data, ?array $meta = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => $meta,
        ], $status);
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
