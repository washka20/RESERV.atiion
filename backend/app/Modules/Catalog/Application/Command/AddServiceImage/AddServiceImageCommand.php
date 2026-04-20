<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\AddServiceImage;

use App\Shared\Application\Media\UploadedFileInterface;

/**
 * Команда загрузки фото услуги.
 *
 * Хранит ссылку на UploadedFile через абстракцию — handler использует
 * MediaStorage, не зависит от Laravel UploadedFile.
 */
final readonly class AddServiceImageCommand
{
    public function __construct(
        public string $serviceId,
        public UploadedFileInterface $file,
    ) {}
}
