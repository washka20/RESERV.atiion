<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Page;

use App\Modules\Booking\Application\Command\GenerateTimeSlots\GenerateTimeSlotsCommand;
use App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Shared\Application\Bus\CommandBusInterface;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Throwable;
use UnitEnum;

/**
 * Админская страница batch-генерации TimeSlot'ов через форму.
 *
 * Собирает параметры генерации (service, date range, time range, duration, break,
 * exclude weekdays) и диспатчит GenerateTimeSlotsCommand через CommandBus.
 * Бизнес-логика в GenerateTimeSlotsHandler — эта страница только UI + валидация.
 */
final class GenerateTimeSlotsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.booking.generate-time-slots';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Booking';

    protected static ?string $title = 'Generate Time Slots';

    protected static ?string $navigationLabel = 'Generate Slots';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * Форма страницы. Использует statePath('data') для привязки к public $data.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_id')
                    ->label('Service')
                    ->options(
                        fn (): array => ServiceModel::query()
                            ->where('type', 'time_slot')
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all(),
                    )
                    ->searchable()
                    ->required(),
                DatePicker::make('date_from')
                    ->label('Date from')
                    ->required()
                    ->minDate(today()),
                DatePicker::make('date_to')
                    ->label('Date to')
                    ->required()
                    ->minDate(today())
                    ->afterOrEqual('date_from'),
                TimePicker::make('time_from')
                    ->label('Time from')
                    ->required()
                    ->seconds(false),
                TimePicker::make('time_to')
                    ->label('Time to')
                    ->required()
                    ->seconds(false)
                    ->after('time_from'),
                TextInput::make('slot_duration_minutes')
                    ->label('Slot duration (minutes)')
                    ->numeric()
                    ->integer()
                    ->default(60)
                    ->required()
                    ->minValue(15)
                    ->maxValue(480),
                TextInput::make('break_minutes')
                    ->label('Break between slots (minutes)')
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(240),
                CheckboxList::make('exclude_days_of_week')
                    ->label('Exclude days of week')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Generate')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('submit'),
        ];
    }

    /**
     * Обработчик submit. Валидирует форму, диспатчит команду, показывает notification.
     */
    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            /** @var int $count */
            $count = app(CommandBusInterface::class)->dispatch(new GenerateTimeSlotsCommand(
                serviceId: (string) $data['service_id'],
                dateFrom: (string) $data['date_from'],
                dateTo: (string) $data['date_to'],
                timeFrom: substr((string) $data['time_from'], 0, 5),
                timeTo: substr((string) $data['time_to'], 0, 5),
                slotDurationMinutes: (int) $data['slot_duration_minutes'],
                breakMinutes: (int) ($data['break_minutes'] ?? 0),
                excludeDaysOfWeek: array_map('intval', (array) ($data['exclude_days_of_week'] ?? [])),
            ));
        } catch (Throwable $e) {
            Notification::make()
                ->title('Cannot generate time slots')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Time slots generated')
            ->body(sprintf('%d slot(s) created', $count))
            ->success()
            ->send();

        $this->redirect(TimeSlotResource::getUrl('index'));
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }
}
