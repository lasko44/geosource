<?php

namespace App\Nova;

use Illuminate\Support\Str;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class PendingJob extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string
     */
    public static $model = \App\Models\Job::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'queue', 'payload',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Pending Jobs';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Pending Job';
    }

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'System';

    /**
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Queue')
                ->sortable()
                ->filterable(),

            Text::make('Job', function () {
                return $this->short_name;
            }),

            Text::make('Full Job Name', function () {
                return $this->display_name;
            })->onlyOnDetail(),

            Badge::make('Status', function () {
                if ($this->reserved_at) {
                    return 'Processing';
                }
                if ($this->available_at && $this->available_at->isFuture()) {
                    return 'Delayed';
                }

                return 'Pending';
            })->map([
                'Processing' => 'info',
                'Delayed' => 'warning',
                'Pending' => 'success',
            ]),

            Number::make('Attempts')
                ->sortable(),

            DateTime::make('Available At', 'available_at')
                ->sortable(),

            DateTime::make('Reserved At', 'reserved_at')
                ->sortable()
                ->onlyOnDetail(),

            DateTime::make('Created At', 'created_at')
                ->sortable(),

            Code::make('Payload')
                ->json()
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
        return [];
    }

    /**
     * Determine if the resource can be created.
     */
    public static function authorizedToCreate(\Illuminate\Http\Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the resource can be updated.
     */
    public function authorizedToUpdate(\Illuminate\Http\Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the resource can be replicated.
     */
    public function authorizedToReplicate(\Illuminate\Http\Request $request): bool
    {
        return false;
    }
}
