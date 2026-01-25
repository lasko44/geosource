<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class ScheduledScan extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\ScheduledScan>
     */
    public static $model = \App\Models\ScheduledScan::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'uuid', 'name', 'url',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Scheduled Scans';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Scheduled Scan';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('UUID')
                ->onlyOnDetail()
                ->copyable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            URL::make('URL')
                ->displayUsing(fn ($value) => strlen($value) > 40 ? substr($value, 0, 40).'...' : $value)
                ->sortable()
                ->rules('required', 'url'),

            BelongsTo::make('User')
                ->sortable()
                ->filterable(),

            BelongsTo::make('Team')
                ->sortable()
                ->filterable()
                ->nullable(),

            new Panel('Schedule Configuration', $this->scheduleFields()),

            new Panel('Status & Statistics', $this->statusFields()),

            HasMany::make('Scans', 'scans', Scan::class),
        ];
    }

    /**
     * Get the schedule configuration fields.
     */
    protected function scheduleFields(): array
    {
        return [
            Select::make('Frequency')
                ->options([
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                ])
                ->displayUsingLabels()
                ->sortable()
                ->filterable()
                ->rules('required'),

            Select::make('Day of Week', 'day_of_week')
                ->options([
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ])
                ->displayUsingLabels()
                ->help('Only applies to weekly frequency')
                ->hideFromIndex()
                ->nullable(),

            Number::make('Day of Month', 'day_of_month')
                ->min(1)
                ->max(28)
                ->help('Day 1-28 of the month (only applies to monthly frequency)')
                ->hideFromIndex()
                ->nullable(),

            DateTime::make('Scheduled Time', 'scheduled_time')
                ->help('Time of day to run the scan')
                ->hideFromIndex(),

            Text::make('Schedule', function () {
                return $this->schedule_description ?? 'Not configured';
            })->onlyOnIndex(),

            Text::make('Schedule Description', function () {
                return $this->schedule_description ?? 'Not configured';
            })->onlyOnDetail(),
        ];
    }

    /**
     * Get the status fields.
     */
    protected function statusFields(): array
    {
        return [
            Boolean::make('Active', 'is_active')
                ->sortable()
                ->filterable(),

            Badge::make('Status', function () {
                if (! $this->is_active) {
                    return 'Paused';
                }
                if ($this->next_run_at && $this->next_run_at->isPast()) {
                    return 'Due';
                }

                return 'Scheduled';
            })->map([
                'Paused' => 'warning',
                'Due' => 'info',
                'Scheduled' => 'success',
            ])->onlyOnIndex(),

            DateTime::make('Next Run', 'next_run_at')
                ->sortable()
                ->exceptOnForms(),

            Text::make('Time Until Next Run', function () {
                if (! $this->next_run_at) {
                    return 'Not scheduled';
                }
                if ($this->next_run_at->isPast()) {
                    return 'Due now (waiting for scheduler)';
                }

                return $this->next_run_at->diffForHumans();
            })->onlyOnDetail(),

            DateTime::make('Last Run', 'last_run_at')
                ->sortable()
                ->exceptOnForms(),

            Number::make('Total Runs', 'total_runs')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms()
                ->onlyOnDetail(),

            DateTime::make('Updated At')
                ->sortable()
                ->exceptOnForms()
                ->onlyOnDetail(),
        ];
    }

    /**
     * Get the cards available for the resource.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [
            Actions\RunScheduledScanNow::make()
                ->showInline()
                ->confirmText('This will immediately queue a scan for this URL. Continue?')
                ->confirmButtonText('Run Now')
                ->cancelButtonText('Cancel'),
        ];
    }
}
