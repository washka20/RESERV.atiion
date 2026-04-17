<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function bookingAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.bookingIssueJwt($user)];
}

it('returns TIME_SLOT availability with slots array', function (): void {
    $user = bookingInsertUser('avail-ts@test.com');
    $categoryId = insertCategory('AvailabilityTS');
    $service = saveTimeSlotService('Time slot svc', $categoryId);

    $date = date('Y-m-d', strtotime('+2 days'));
    $slotId = bookingInsertTimeSlot(
        $service->id(),
        $date.' 10:00',
        $date.' 11:00',
    );

    $response = $this->withHeaders(bookingAuthHeader($user))
        ->getJson("/api/v1/services/{$service->id()->toString()}/availability?type=time_slot&date={$date}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'error' => null,
            'data' => [
                'type' => 'time_slot',
                'available' => true,
            ],
        ])
        ->assertJsonStructure([
            'data' => ['type', 'available', 'slots' => [['id', 'start_at', 'end_at']]],
        ]);

    $ids = collect($response->json('data.slots'))->pluck('id')->all();
    expect($ids)->toContain($slotId);
});

it('returns QUANTITY availability with total/booked/available_quantity/requested', function (): void {
    $user = bookingInsertUser('avail-q@test.com');
    $categoryId = insertCategory('AvailabilityQ');

    $entity = Service::createQuantity(
        ServiceId::generate(),
        'Quantity svc',
        'desc',
        Money::fromCents(500000, 'RUB'),
        10,
        $categoryId,
        null,
    );
    app(ServiceRepositoryInterface::class)->save($entity);

    $checkIn = date('Y-m-d', strtotime('+2 days'));
    $checkOut = date('Y-m-d', strtotime('+5 days'));

    $response = $this->withHeaders(bookingAuthHeader($user))
        ->getJson(sprintf(
            '/api/v1/services/%s/availability?type=quantity&check_in=%s&check_out=%s&requested=2',
            $entity->id()->toString(),
            $checkIn,
            $checkOut,
        ));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'error' => null,
            'data' => [
                'type' => 'quantity',
                'available' => true,
                'total' => 10,
                'booked' => 0,
                'available_quantity' => 10,
                'requested' => 2,
            ],
        ]);
});

it('returns 404 when service does not exist', function (): void {
    $user = bookingInsertUser('avail-404@test.com');

    $randomId = (string) Str::uuid();
    $response = $this->withHeaders(bookingAuthHeader($user))
        ->getJson("/api/v1/services/{$randomId}/availability?type=time_slot&date=".date('Y-m-d', strtotime('+2 days')));

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'CATALOG_SERVICE_NOT_FOUND'],
        ]);
});

it('returns 401 NO_TOKEN without Authorization header', function (): void {
    $categoryId = insertCategory('AvailabilityAnon');
    $service = saveTimeSlotService('Anon svc', $categoryId);

    $response = $this->getJson(
        "/api/v1/services/{$service->id()->toString()}/availability?type=time_slot&date=".date('Y-m-d', strtotime('+2 days')),
    );

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'NO_TOKEN']]);
});

it('returns 422 when type query param is missing', function (): void {
    $user = bookingInsertUser('avail-422@test.com');
    $categoryId = insertCategory('AvailabilityNoType');
    $service = saveTimeSlotService('No type svc', $categoryId);

    $response = $this->withHeaders(bookingAuthHeader($user))
        ->getJson("/api/v1/services/{$service->id()->toString()}/availability");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});
