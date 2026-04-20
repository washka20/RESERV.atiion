<?php

declare(strict_types=1);

use App\Shared\Application\Media\MediaStorageInterface;
use App\Shared\Application\Media\MediaValidationException;
use App\Shared\Infrastructure\Media\LaravelUploadedFile;
use App\Shared\Infrastructure\Media\S3MediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
    config()->set('media.disk', 's3');
    config()->set('media.max_size_kb', 10240);
    config()->set('media.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    config()->set('media.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    config()->set('media.signed_url.ttl_minutes', 60);
});

it('store saves file и возвращает path', function (): void {
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('photo.jpg'));

    $path = app(S3MediaStorage::class)->store($file, 'services/abc-123');

    expect($path)->toStartWith('services/abc-123/')->toEndWith('.jpg');
    Storage::disk('s3')->assertExists($path);
});

it('store генерирует уникальные имена для одинаковых файлов', function (): void {
    $storage = app(S3MediaStorage::class);
    $file1 = new LaravelUploadedFile(UploadedFile::fake()->image('same.jpg'));
    $file2 = new LaravelUploadedFile(UploadedFile::fake()->image('same.jpg'));

    $p1 = $storage->store($file1, 'services/x');
    $p2 = $storage->store($file2, 'services/x');

    expect($p1)->not->toBe($p2);
});

it('store отклоняет запрещённый mime', function (): void {
    $file = new LaravelUploadedFile(UploadedFile::fake()->create('evil.exe', 100, 'application/x-msdownload'));

    expect(fn () => app(S3MediaStorage::class)->store($file, 'services/x'))
        ->toThrow(MediaValidationException::class);
});

it('store отклоняет oversized файл', function (): void {
    config()->set('media.max_size_kb', 10);
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('big.jpg')->size(100));

    expect(fn () => app(S3MediaStorage::class)->store($file, 'services/x'))
        ->toThrow(MediaValidationException::class);
});

it('store отклоняет запрещённое расширение', function (): void {
    config()->set('media.allowed_extensions', ['png']);
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('photo.jpg'));

    expect(fn () => app(S3MediaStorage::class)->store($file, 'services/x'))
        ->toThrow(MediaValidationException::class);
});

it('delete удаляет файл', function (): void {
    $storage = app(S3MediaStorage::class);
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('photo.jpg'));
    $path = $storage->store($file, 'services/abc');

    $storage->delete($path);

    Storage::disk('s3')->assertMissing($path);
});

it('delete идемпотентен для несуществующего файла', function (): void {
    $storage = app(S3MediaStorage::class);

    $storage->delete('services/never-existed/file.jpg');

    expect(true)->toBeTrue();
});

it('exists возвращает корректное состояние', function (): void {
    $storage = app(S3MediaStorage::class);
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('p.jpg'));
    $path = $storage->store($file, 'services/y');

    expect($storage->exists($path))->toBeTrue();

    $storage->delete($path);
    expect($storage->exists($path))->toBeFalse();
});

it('signedUrl возвращает non-empty URL с path внутри', function (): void {
    $storage = app(S3MediaStorage::class);
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('p.jpg'));
    $path = $storage->store($file, 'services/z');

    $url = $storage->signedUrl($path, 5);

    expect($url)->not->toBeEmpty()->toContain($path);
});

it('signedUrl использует config ttl по умолчанию если не передан', function (): void {
    $storage = app(S3MediaStorage::class);
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('p.jpg'));
    $path = $storage->store($file, 'services/default-ttl');

    $url = $storage->signedUrl($path);

    expect($url)->not->toBeEmpty();
});

it('S3MediaStorage implements MediaStorageInterface', function (): void {
    expect(app(S3MediaStorage::class))->toBeInstanceOf(MediaStorageInterface::class);
});
