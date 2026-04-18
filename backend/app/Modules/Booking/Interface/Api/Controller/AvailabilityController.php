<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Controller;

use App\Modules\Booking\Application\DTO\AvailabilityDTO;
use App\Modules\Booking\Application\Query\CheckAvailability\CheckAvailabilityQuery;
use App\Modules\Booking\Interface\Api\Request\CheckAvailabilityRequest;
use App\Modules\Booking\Interface\Api\Resource\AvailabilityResource;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Http\JsonResponse;

/**
 * Эндпоинт проверки доступности услуги на дату/диапазон.
 *
 * GET /api/v1/services/{service}/availability?type=time_slot&date=YYYY-MM-DD
 * GET /api/v1/services/{service}/availability?type=quantity&check_in=...&check_out=...&requested=N
 */
final readonly class AvailabilityController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function check(string $service, CheckAvailabilityRequest $request): JsonResponse
    {
        $params = $this->extractParams($request);

        try {
            /** @var AvailabilityDTO $dto */
            $dto = $this->queryBus->ask(new CheckAvailabilityQuery(
                serviceId: $service,
                params: $params,
            ));
        } catch (ServiceNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        }

        return $this->envelope(new AvailabilityResource($dto));
    }

    /**
     * Отбирает только type-specific ключи из валидированных данных для CheckAvailabilityQuery.
     *
     * @return array<string, mixed>
     */
    private function extractParams(CheckAvailabilityRequest $request): array
    {
        $type = (string) $request->string('type');
        $data = $request->validated();

        if ($type === 'time_slot') {
            return [
                'date' => $data['date'] ?? null,
            ];
        }

        if ($type === 'quantity') {
            return [
                'check_in' => $data['check_in'] ?? null,
                'check_out' => $data['check_out'] ?? null,
                'requested' => isset($data['requested']) ? (int) $data['requested'] : null,
            ];
        }

        return [];
    }

    /**
     * @param  mixed  $data
     */
    private function envelope($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => null,
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
