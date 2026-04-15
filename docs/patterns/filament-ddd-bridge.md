# Filament ↔ DDD bridge

Filament admin отдаёт HTML и управляет сущностями, но доменные инварианты живут в aggregate roots. Этот паттерн разделяет ответственность: read напрямую, write через Command Bus.

## Принцип: read = Eloquent, write = Command Bus

- **Read** (таблицы, формы-отображения, фильтры, search) — Eloquent Model напрямую. Relations, scopes, accessors — ок. Filament query builder оптимизирован под Eloquent, боремся с ним только себе в убыток.
- **Write** (submit формы, custom actions, bulk actions) — никогда `Model::create()` / `$record->save()`. Всегда через `CommandBusInterface::dispatch($command)`. Handler валидирует инварианты, собирает aggregate, публикует domain events.

## Read side

Filament Resource указывает `$model = UserModel::class` и строит форму / таблицу на Schema API:

```php
use Filament\Schemas\Schema;
use Filament\Tables\Table;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        TextInput::make('email')->email()->required(),
        TextInput::make('first_name')->required(),
        // ...
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('email')->searchable(),
        TextColumn::make('full_name'),
    ])->filters([/* ... */]);
}
```

## Write side

`handleRecordCreation()` и `handleRecordUpdate()` перехватывают submit формы до того, как Filament дернёт `save()` на модели:

```php
// Pages/CreateUser.php
protected function handleRecordCreation(array $data): Model
{
    $command = new RegisterUserCommand(
        email: $data['email'],
        plaintextPassword: $data['password'],
        firstName: $data['first_name'],
        lastName: $data['last_name'],
        middleName: $data['middle_name'] ?? null,
    );

    /** @var UserId $userId */
    $userId = app(CommandBusInterface::class)->dispatch($command);

    return UserModel::query()->findOrFail($userId->toString());
}

// Pages/EditUser.php
protected function handleRecordUpdate(Model $record, array $data): Model
{
    $command = new UpdateUserCommand(
        userId: (string) $record->id,
        email: $data['email'] ?? null,
        firstName: $data['first_name'] ?? null,
        lastName: $data['last_name'] ?? null,
        middleName: $data['middle_name'] ?? null,
    );

    app(CommandBusInterface::class)->dispatch($command);

    return $record->refresh();
}
```

Возврат `Model` нужен Filament UI (редирект на edit, сообщения, reload таблицы). Handler возвращает `UserId` — ищем модель по id и отдаём её Filament.

## Custom Actions

Тот же паттерн — dispatch команды, notification, refresh:

```php
final class AssignRoleAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false)
            ->schema([
                Select::make('role_name')
                    ->options(/* RoleName::cases() */)
                    ->required(),
            ])
            ->action(function (array $data, UserModel $record): void {
                $command = new AssignRoleCommand(
                    userId: (string) $record->id,
                    roleName: RoleName::from($data['role_name']),
                );

                app(CommandBusInterface::class)->dispatch($command);

                Notification::make()->title('Role assigned')->success()->send();
            });
    }
}
```

## Filament 5 — мини-напоминание

- Form / Infolist / Table базируются на `Filament\Schemas\Schema` (v4+).
- Все Action классы — из `Filament\Actions\*` (не `Filament\Tables\Actions\*` и не `Filament\Forms\Actions\*` — эти namespace-ы убраны в v4).
- Сигнатуры `handleRecord*` не менялись при переходе v3 → v5.
- Подробности: `.claude/rules/filament.md`.

## FAQ

**Нужна валидация?**
Filament Forms покрывают UI-валидацию (`required`, `email`, `minLength`, `unique`). Доменные инварианты живут в конструкторах / фабричных методах aggregate. Handler бросает доменное исключение, Filament ловит и показывает сообщение. FormRequest не нужен.

**Где диспатчить?**
Внутри `handleRecordCreation` / `handleRecordUpdate` / `->action(...)`. Всегда через `app(CommandBusInterface::class)->dispatch($command)` — DI работает через сервис-контейнер, конструктор page-классов Filament трогать не нужно.

**Модель хранит UUID — что возвращать?**
Handler возвращает VO (`UserId`, `BookingId`). В page методе: `Model::query()->findOrFail($id->toString())`.

## Don't do

- `UserModel::create(['email' => ...])` в `handleRecordCreation` — ломает доменные инварианты и теряет domain events.
- `$record->password = $plain; $record->save()` — обходит `HashedPassword` VO и hasher.
- FormRequest для Filament-форм — UI-валидация это ответственность Filament Forms. Feature-тесты Filament проверяют happy path + error path без FormRequest.
- `Model::save()` внутри `->action(...)` Custom Action — всегда через команду.

## Related

- [ADR-002: Filament для admin panel](../adr/002-filament-for-admin.md)
- [ADR-007: Read side без Eloquent](../adr/007-read-side-without-eloquent.md) — применимо к **customer API**, НЕ к Filament. В Filament Eloquent разрешён.
- [ADR-010: Spatie Permission с доменными ролями](../adr/010-spatie-permission-with-domain-roles.md)
- [.claude/rules/filament.md](../../.claude/rules/filament.md)
