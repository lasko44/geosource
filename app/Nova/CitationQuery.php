<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class CitationQuery extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CitationQuery>
     */
    public static $model = \App\Models\CitationQuery::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'query';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'uuid', 'query', 'domain', 'brand',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Citation Queries';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Citation Query';
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

            Text::make('Query')
                ->sortable()
                ->rules('required', 'max:500'),

            Text::make('Domain')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Brand')
                ->sortable()
                ->nullable()
                ->hideFromIndex(),

            BelongsTo::make('User')
                ->sortable()
                ->filterable(),

            BelongsTo::make('Team')
                ->nullable()
                ->sortable()
                ->filterable(),

            Boolean::make('Active', 'is_active')
                ->sortable()
                ->filterable(),

            Select::make('Frequency')
                ->options([
                    'manual' => 'Manual',
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                ])
                ->displayUsingLabels()
                ->sortable()
                ->filterable(),

            Text::make('Citation Rate', function () {
                $summary = $this->citation_summary;
                return $summary['citation_rate'] . '%';
            })->exceptOnForms(),

            Text::make('Cited On', function () {
                $summary = $this->citation_summary;
                $cited = $summary['cited_on'] ?? [];
                return count($cited) > 0 ? implode(', ', array_map('ucfirst', $cited)) : 'None';
            })->exceptOnForms()->hideFromIndex(),

            DateTime::make('Last Checked', 'last_checked_at')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Next Check', 'next_check_at')
                ->sortable()
                ->exceptOnForms()
                ->hideFromIndex(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms()
                ->hideFromIndex(),

            HasMany::make('Checks', 'checks', CitationCheck::class),

            HasMany::make('Alerts', 'alerts', CitationAlert::class),
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
