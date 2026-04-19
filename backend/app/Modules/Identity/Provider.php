<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Identity\Application\Query\ListUserMemberships\ListUserMembershipsHandler;
use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Application\Service\UserMembershipsLookupInterface;
use App\Modules\Identity\Domain\Event\UserRoleAssigned;
use App\Modules\Identity\Domain\Event\UserRoleRevoked;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\Repository\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use App\Modules\Identity\Domain\Service\SlugGeneratorInterface;
use App\Modules\Identity\Infrastructure\Auth\BcryptPasswordHasher;
use App\Modules\Identity\Infrastructure\Auth\JwtGuard;
use App\Modules\Identity\Infrastructure\Auth\JwtTokenService;
use App\Modules\Identity\Infrastructure\Persistence\Repository\EloquentMembershipRepository;
use App\Modules\Identity\Infrastructure\Persistence\Repository\EloquentOrganizationRepository;
use App\Modules\Identity\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use App\Modules\Identity\Infrastructure\Persistence\Repository\EloquentUserRepository;
use App\Modules\Identity\Infrastructure\Service\EloquentMembershipLookup;
use App\Modules\Identity\Infrastructure\Service\PgSlugGenerator;
use App\Modules\Identity\Interface\Api\Middleware\MembershipGuardMiddleware;
use App\Shared\Application\Identity\MembershipLookupInterface;
use App\Modules\Identity\Interface\Filament\Listener\SyncSpatieRoleOnUserRoleAssigned;
use App\Modules\Identity\Interface\Filament\Listener\SyncSpatieRoleOnUserRoleRevoked;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Lcobucci\Clock\SystemClock;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(OrganizationRepositoryInterface::class, EloquentOrganizationRepository::class);
        $this->app->bind(MembershipRepositoryInterface::class, EloquentMembershipRepository::class);
        $this->app->bind(PasswordHasherInterface::class, BcryptPasswordHasher::class);
        $this->app->bind(SlugGeneratorInterface::class, PgSlugGenerator::class);
        $this->app->bind(UserMembershipsLookupInterface::class, ListUserMembershipsHandler::class);
        $this->app->bind(MembershipLookupInterface::class, EloquentMembershipLookup::class);

        $this->app->singleton(JwtTokenServiceInterface::class, static function ($app): JwtTokenService {
            return new JwtTokenService(
                secret: (string) config('jwt.secret'),
                issuer: (string) config('jwt.issuer'),
                audience: (string) config('jwt.audience'),
                accessTtl: (int) config('jwt.ttl'),
                refreshTtl: (int) config('jwt.refresh_ttl'),
                clock: SystemClock::fromUTC(),
            );
        });
    }

    public function boot(): void
    {
        Auth::extend('jwt', function ($app, string $name, array $config): JwtGuard {
            return new JwtGuard(
                $app->make(JwtTokenServiceInterface::class),
                Auth::createUserProvider($config['provider']),
                $app->make('request'),
            );
        });

        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('org.member', MembershipGuardMiddleware::class);

        Event::listen(UserRoleAssigned::class, [SyncSpatieRoleOnUserRoleAssigned::class, 'handle']);
        Event::listen(UserRoleRevoked::class, [SyncSpatieRoleOnUserRoleRevoked::class, 'handle']);
    }
}
