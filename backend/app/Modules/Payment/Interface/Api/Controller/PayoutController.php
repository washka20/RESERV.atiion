<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Api\Controller;

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Application\Query\GetOrganizationBySlug\GetOrganizationBySlugQuery;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Payment\Application\Command\UpdatePayoutSettings\UpdatePayoutSettingsCommand;
use App\Modules\Payment\Application\DTO\OrganizationStatsDTO;
use App\Modules\Payment\Application\DTO\PayoutSettingsDTO;
use App\Modules\Payment\Application\DTO\PayoutTransactionDTO;
use App\Modules\Payment\Application\Query\GetOrganizationStats\GetOrganizationStatsQuery;
use App\Modules\Payment\Application\Query\GetPayoutSettings\GetPayoutSettingsQuery;
use App\Modules\Payment\Application\Query\ListPayoutsByOrganization\ListPayoutsByOrganizationQuery;
use App\Modules\Payment\Interface\Api\Request\UpdatePayoutSettingsRequest;
use App\Modules\Payment\Interface\Api\Resource\OrganizationStatsResource;
use App\Modules\Payment\Interface\Api\Resource\PayoutSettingsResource;
use App\Modules\Payment\Interface\Api\Resource\PayoutTransactionResource;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Owner/member endpoints payouts + настроек выплат + статистики организации.
 *
 * Авторизация:
 *  - GET /payouts              — middleware org.member:payouts.view
 *  - GET /payout-settings      — middleware org.member:payouts.view
 *  - PUT /payout-settings      — middleware org.member:payouts.manage (owner-only)
 *  - GET /stats                — middleware org.member:analytics.view
 *
 * Ownership/role matrix живёт в {@see MembershipRole::PERMISSIONS}.
 */
final readonly class PayoutController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function index(Request $request, string $slug): JsonResponse
    {
        $organization = $this->loadOrganizationOr404($slug);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $page = max(1, (int) ($request->input('page') ?? 1));
        $perPage = max(1, min(100, (int) ($request->input('per_page') ?? 20)));

        /** @var array{items: list<PayoutTransactionDTO>, meta: array{page: int, per_page: int, total: int, last_page: int}} $result */
        $result = $this->queryBus->ask(new ListPayoutsByOrganizationQuery(
            organizationId: $organization->id,
            page: $page,
            perPage: $perPage,
        ));

        return $this->envelope(
            data: PayoutTransactionResource::collection($result['items']),
            meta: $result['meta'],
        );
    }

    public function settingsShow(string $slug): JsonResponse
    {
        $organization = $this->loadOrganizationOr404($slug);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        /** @var PayoutSettingsDTO|null $settings */
        $settings = $this->queryBus->ask(new GetPayoutSettingsQuery($organization->id));

        if ($settings === null) {
            return $this->error('PAYOUT_SETTINGS_NOT_CONFIGURED', 'Payout settings are not configured', 404);
        }

        return $this->envelope(new PayoutSettingsResource($settings));
    }

    public function settingsUpdate(UpdatePayoutSettingsRequest $request, string $slug): JsonResponse
    {
        $organization = $this->loadOrganizationOr404($slug);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $userId = $this->actorUserId($request);

        try {
            $this->commandBus->dispatch(new UpdatePayoutSettingsCommand(
                userId: $userId,
                organizationId: $organization->id,
                bankName: (string) $request->string('bank_name'),
                accountNumber: (string) $request->string('account_number'),
                accountHolder: (string) $request->string('account_holder'),
                bic: (string) $request->string('bic'),
                schedule: (string) $request->string('payout_schedule'),
                minimumPayoutCents: (int) $request->input('minimum_payout_cents'),
            ));
        } catch (AuthorizationException $e) {
            return $this->error('FORBIDDEN_INSUFFICIENT_ROLE', $e->getMessage(), 403);
        }

        /** @var PayoutSettingsDTO|null $settings */
        $settings = $this->queryBus->ask(new GetPayoutSettingsQuery($organization->id));
        if ($settings === null) {
            return $this->error('PAYOUT_SETTINGS_NOT_FOUND', 'Payout settings disappeared after update', 500);
        }

        return $this->envelope(new PayoutSettingsResource($settings));
    }

    public function stats(string $slug): JsonResponse
    {
        $organization = $this->loadOrganizationOr404($slug);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        /** @var OrganizationStatsDTO $stats */
        $stats = $this->queryBus->ask(new GetOrganizationStatsQuery($organization->id));

        return $this->envelope(new OrganizationStatsResource($stats));
    }

    /**
     * Возвращает OrganizationDTO или JsonResponse 404, если slug не найден.
     */
    private function loadOrganizationOr404(string $slug): OrganizationDTO|JsonResponse
    {
        try {
            /** @var OrganizationDTO $dto */
            $dto = $this->queryBus->ask(new GetOrganizationBySlugQuery($slug));

            return $dto;
        } catch (OrganizationNotFoundException $e) {
            return $this->error('ORGANIZATION_NOT_FOUND', $e->getMessage(), 404);
        }
    }

    private function actorUserId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            throw new RuntimeException('Unauthorized actor');
        }

        return (string) $user->getAuthIdentifier();
    }

    /**
     * @param  mixed  $data
     * @param  array<string, mixed>|null  $meta
     */
    private function envelope($data, ?array $meta = null, int $status = 200): JsonResponse
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
