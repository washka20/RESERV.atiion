<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Service;

use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\Service\SlugGeneratorInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use Illuminate\Support\Str;

/**
 * Реализация SlugGeneratorInterface поверх Laravel Str::slug + Postgres-backed
 * проверки уникальности через OrganizationRepository.
 *
 * Обрабатывает RU транслитерацию (с локаль-оверрайдом для "ц → ts"),
 * режет длинные строки под MAX_BASE_LENGTH, дополняет короткие случайным hex
 * и разрешает collisions инкрементным суффиксом "-2", "-3", ....
 */
final class PgSlugGenerator implements SlugGeneratorInterface
{
    private const MIN_LENGTH = 3;

    private const MAX_LENGTH = 64;

    /** Оставляем запас под суффикс "-NN" при collisions. */
    private const MAX_BASE_LENGTH = 58;

    /**
     * Доводим Laravel-ру-мап до BGN-стиля: по умолчанию "ц → c", нам нужно "ts".
     *
     * @var array<string, string>
     */
    private const RU_DICT_OVERRIDE = [
        'ц' => 'ts',
        'Ц' => 'Ts',
    ];

    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    public function generate(string $source): OrganizationSlug
    {
        $base = $this->normalize($source);

        $candidate = $base;
        $suffix = 2;
        while ($this->organizations->existsBySlug(new OrganizationSlug($candidate))) {
            $suffixStr = '-'.$suffix;
            $trimmedBase = mb_substr($base, 0, self::MAX_LENGTH - strlen($suffixStr));
            $trimmedBase = rtrim($trimmedBase, '-');
            $candidate = $trimmedBase.$suffixStr;
            $suffix++;
        }

        return new OrganizationSlug($candidate);
    }

    /**
     * Приводит строку к валидному базовому slug'у: транслитерация, lower, [a-z0-9-],
     * collapse "--", trim "-", длина в [MIN_LENGTH, MAX_BASE_LENGTH]. При слишком коротком
     * результате добавляет случайный hex-суффикс.
     */
    private function normalize(string $source): string
    {
        $slug = Str::slug($source, '-', 'ru', self::RU_DICT_OVERRIDE);

        // Collapse double dashes и trim.
        $slug = (string) preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '' || strlen($slug) < self::MIN_LENGTH) {
            $slug = $this->padToMin($slug);
        }

        if (strlen($slug) > self::MAX_BASE_LENGTH) {
            $slug = rtrim(mb_substr($slug, 0, self::MAX_BASE_LENGTH), '-');
        }

        return $slug;
    }

    /**
     * Дополняет короткий slug случайным hex'ом, но сохраняет базовые символы,
     * если они есть (чтобы "ab" → "ab-xx...").
     */
    private function padToMin(string $slug): string
    {
        $needed = self::MIN_LENGTH - strlen($slug);
        $padLength = max($needed, 4);
        $random = substr(bin2hex(random_bytes($padLength)), 0, $padLength);

        if ($slug === '') {
            return $random;
        }

        return $slug.'-'.$random;
    }
}
