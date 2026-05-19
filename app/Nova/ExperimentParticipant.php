<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Nova resource for viewing experiment participant assignments and conversions.
 */
class ExperimentParticipant extends Resource
{
    public static $model = \App\Models\ExperimentParticipant::class;

    public static $title = 'visitor_id';

    public static $search = ['id', 'visitor_id'];

    public static $displayInNavigation = true;

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Participants';
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

            BelongsTo::make('Experiment')
                ->sortable()
                ->filterable(),

            Text::make('Visitor ID')
                ->sortable()
                ->hideFromIndex(),

            BelongsTo::make('User')
                ->nullable()
                ->sortable()
                ->filterable(),

            Badge::make('Variant')
                ->map([
                    'control' => 'info',
                    'scan_input' => 'success',
                    'citation_check' => 'warning',
                ])
                ->sortable()
                ->filterable(),

            Badge::make('Converted', function () {
                return $this->converted_at ? 'Yes' : 'No';
            })->map([
                'Yes' => 'success',
                'No' => 'warning',
            ])->filterable(function ($request, $query, $value) {
                if ($value === 'Yes') {
                    $query->whereNotNull('converted_at');
                } else {
                    $query->whereNull('converted_at');
                }
            }),

            DateTime::make('Converted At')
                ->sortable()
                ->hideFromIndex(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms(),
        ];
    }

    /**
     * Prevent creating/updating/deleting participants from Nova.
     */
    public static function authorizedToCreate(\Illuminate\Http\Request $request): bool
    {
        return false;
    }

    public function authorizedToUpdate(\Illuminate\Http\Request $request): bool
    {
        return false;
    }

    public function authorizedToDelete(\Illuminate\Http\Request $request): bool
    {
        return false;
    }

    /**
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
