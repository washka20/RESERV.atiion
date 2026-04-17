<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Controller;

use App\Modules\Booking\Application\Command\CancelBooking\CancelBookingCommand;
use App\Modules\Booking\Application\Command\CreateBooking\CreateBookingCommand;
use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Application\DTO\BookingListResult;
use App\Modules\Booking\Application\Query\GetBooking\GetBookingQuery;
use App\Modules\Booking\Application\Query\ListUserBookings\ListUserBookingsQuery;
use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Exception\CancellationNotAllowedException;
use App\Modules\Booking\Domain\Exception\InsufficientQuantityException;
use App\Modules\Booking\Domain\Exception\InvalidBookingTypeException;
use App\Modules\Booking\Domain\Exception\SlotUnavailableException;
use App\Modules\Booking\Interface\Api\Request\CreateBookingRequest;
use App\Modules\Booking\Interface\Api\Resource\BookingListItemResource;
use App\Modules\Booking\Interface\Api\Resource\BookingResource;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Публичные эндпоинты бронирований для авторизованного пользователя.
 *
 * Ownership enforced Application-слоем: ListUserBookings фильтрует по userId из JWT,
 * GetBooking бросает RuntimeException('Forbidden') если actor ≠ owner и не admin.
 */
final readonly class BookingController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);
        $page = max(1, (int) ($request->input('page') ?? 1));
        $perPage = (int) ($request->input('per_page') ?? 20);
        $perPage = max(1, min(100, $perPage));
        $status = $this->nullableString($request->input('status'));

        /** @var BookingListResult $result */
        $result = $this->queryBus->ask(new ListUserBookingsQuery(
            userId: $userId,
            status: $status,
            page: $page,
            perPage: $perPage,
        ));

        return $this->envelope(
            data: BookingListItemResource::collection($result->data),
            meta: [
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'last_page' => $result->lastPage,
            ],
        );
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        try {
            /** @var BookingDTO $dto */
            $dto = $this->queryBus->ask(new GetBookingQuery(
                bookingId: $id,
                actorUserId: $userId,
                isAdmin: false,
            ));
        } catch (BookingNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        }

        return $this->envelope(new BookingResource($dto));
    }

    public function store(CreateBookingRequest $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $command = new CreateBookingCommand(
            userId: $userId,
            serviceId: (string) $request->string('service_id'),
            slotId: $this->nullableString($request->input('slot_id')),
            checkIn: $this->nullableString($request->input('check_in')),
            checkOut: $this->nullableString($request->input('check_out')),
            quantity: $request->input('quantity') !== null ? (int) $request->input('quantity') : null,
            notes: $this->nullableString($request->input('notes')),
        );

        try {
            /** @var BookingDTO $dto */
            $dto = $this->commandBus->dispatch($command);
        } catch (ServiceNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (SlotUnavailableException|InsufficientQuantityException|InvalidBookingTypeException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        }

        return $this->envelope(new BookingResource($dto), status: 201);
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        try {
            $this->commandBus->dispatch(new CancelBookingCommand(
                bookingId: $id,
                actorUserId: $userId,
                isAdmin: false,
            ));
        } catch (BookingNotFoundException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 404);
        } catch (CancellationNotAllowedException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), 409);
        } catch (RuntimeException) {
            return $this->error('FORBIDDEN', 'Forbidden', 403);
        }

        /** @var BookingDTO $dto */
        $dto = $this->queryBus->ask(new GetBookingQuery(
            bookingId: $id,
            actorUserId: $userId,
            isAdmin: false,
        ));

        return $this->envelope(new BookingResource($dto));
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
