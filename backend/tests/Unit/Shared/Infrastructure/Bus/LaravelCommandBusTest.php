<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Infrastructure\Bus\LaravelCommandBus;
use Illuminate\Container\Container;
use RuntimeException;
use Tests\TestCase;

final class FakeCreateThingCommand
{
    public function __construct(public readonly string $name) {}
}

final class FakeCreateThingHandler
{
    public function handle(FakeCreateThingCommand $command): string
    {
        return 'created:' . $command->name;
    }
}

final class LaravelCommandBusTest extends TestCase
{
    public function test_resolves_handler_by_convention_and_returns_result(): void
    {
        $container = new Container();
        $container->bind(FakeCreateThingHandler::class, fn () => new FakeCreateThingHandler());

        $bus = new LaravelCommandBus($container);

        $result = $bus->dispatch(new FakeCreateThingCommand('widget'));

        $this->assertSame('created:widget', $result);
    }

    public function test_throws_when_handler_class_missing(): void
    {
        $bus = new LaravelCommandBus(new Container());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Handler.*not found/');

        $bus->dispatch(new class {});
    }
}
