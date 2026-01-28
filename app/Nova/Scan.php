<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class Scan extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Scan>
     */
    public static $model = \App\Models\Scan::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'uuid', 'url', 'title',
    ];

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

            Badge::make('Type', function () {
                return $this->scheduled_scan_id ? 'Scheduled' : 'Manual';
            })->map([
                'Scheduled' => 'info',
                'Manual' => 'success',
            ])->filterable(function ($request, $query, $value, $attribute) {
                if ($value === 'Scheduled') {
                    $query->whereNotNull('scheduled_scan_id');
                } else {
                    $query->whereNull('scheduled_scan_id');
                }
            }),

            BelongsTo::make('User')
                ->sortable()
                ->filterable(),

            BelongsTo::make('Scheduled Scan', 'scheduledScan', ScheduledScan::class)
                ->nullable()
                ->hideFromIndex(),

            Text::make('Title')
                ->sortable()
                ->rules('nullable', 'max:255'),

            URL::make('URL')
                ->displayUsing(fn ($value) => strlen($value) > 50 ? substr($value, 0, 50).'...' : $value)
                ->sortable(),

            Number::make('Score')
                ->sortable()
                ->filterable()
                ->step(0.01),

            Badge::make('Grade')
                ->map([
                    'A+' => 'success',
                    'A' => 'success',
                    'A-' => 'success',
                    'B+' => 'info',
                    'B' => 'info',
                    'B-' => 'info',
                    'C+' => 'warning',
                    'C' => 'warning',
                    'C-' => 'warning',
                    'D+' => 'danger',
                    'D' => 'danger',
                    'D-' => 'danger',
                    'F' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            Badge::make('Status')
                ->map([
                    'pending' => 'warning',
                    'processing' => 'info',
                    'completed' => 'success',
                    'failed' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            Text::make('Error Message')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('Internal Error')
                ->onlyOnDetail()
                ->nullable()
                ->help('Detailed error for debugging - not shown to users'),

            DateTime::make('Scanned At', 'created_at')
                ->sortable()
                ->filterable()
                ->exceptOnForms(),
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
        return [];
    }
}
