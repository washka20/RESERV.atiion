<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Shared\Infrastructure\Media\LaravelUploadedFile;
use App\Shared\Infrastructure\Media\S3MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
});

function signedUrlCategory(): CategoryId
{
    $categoryId = CategoryId::generate();
    CategoryModel::query()->insert([
        'id' => $categoryId->toString(),
        'name' => 'Cat',
        'slug' => 'cat-'.substr($categoryId->toString(), 0, 8),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $categoryId;
}

function signedUrlServiceWithImage(): Service
{
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'Svc',
        'Desc',
        Money::fromCents(100000, 'RUB'),
        Duration::ofMinutes(30),
        signedUrlCategory(),
        null,
        insertOrganizationForTests(),
    );

    $file = new LaravelUploadedFile(UploadedFile::fake()->image('photo.jpg'));
    $path = app(S3MediaStorage::class)->store($file, "services/{$service->id()}");

    $service->addImage(ImagePath::fromString($path));
    app(ServiceRepositoryInterface::class)->save($service);

    return $service;
}

it('GET /services/{id} возвращает images как signed URLs', function (): void {
    $service = signedUrlServiceWithImage();

    $response = $this->getJson('/api/v1/services/'.$service->id()->toString());

    $response->assertStatus(200);

    $images = $response->json('data.images');
    expect($images)->toBeArray()->not->toBeEmpty();
    expect($images[0])->toStartWith('http');
});

it('GET /services/{id} без фото — images пустой array', function (): void {
    $service = Service::createTimeSlot(
        ServiceId::generate(),
        'No image svc',
        'Desc',
        Money::fromCents(100000, 'RUB'),
        Duration::ofMinutes(30),
        signedUrlCategory(),
        null,
        insertOrganizationForTests(),
    );
    app(ServiceRepositoryInterface::class)->save($service);

    $response = $this->getJson('/api/v1/services/'.$service->id()->toString());

    $response->assertStatus(200);
    expect($response->json('data.images'))->toBe([]);
});

it('GET /services возвращает primary_image как signed URL', function (): void {
    signedUrlServiceWithImage();

    $response = $this->getJson('/api/v1/services?perPage=10');

    $response->assertStatus(200);
    $items = $response->json('data');
    expect($items)->toBeArray();
    $withImage = collect($items)->firstWhere('primary_image', '!==', null);
    expect($withImage['primary_image'])->toStartWith('http');
});
