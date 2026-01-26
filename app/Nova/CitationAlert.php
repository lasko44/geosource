<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class CitationAlert extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CitationAlert>
     */
    public static $model = \App\Models\CitationAlert::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'message';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'message', 'platform',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Citation Alerts';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Citation Alert';
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

            Badge::make('Type')
                ->map([
                    'new_citation' => 'success',
                    'lost_citation' => 'danger',
                ])
                ->labels([
                    'new_citation' => 'New Citation',
                    'lost_citation' => 'Lost Citation',
                ])
                ->sortable()
                ->filterable(),

            Badge::make('Platform')
                ->map([
                    'perplexity' => 'info',
                    'openai' => 'success',
                    'claude' => 'warning',
                    'gemini' => 'info',
                    'deepseek' => 'info',
                    'google' => 'danger',
                    'youtube' => 'danger',
                    'facebook' => 'info',
                ])
                ->sortable()
                ->filterable(),

            Text::make('Message')
                ->displayUsing(fn ($value) => strlen($value) > 60 ? substr($value, 0, 60) . '...' : $value)
                ->onlyOnIndex(),

            Text::make('Message')
                ->hideFromIndex(),

            BelongsTo::make('User')
                ->sortable()
                ->filterable(),

            BelongsTo::make('Team')
                ->nullable()
                ->hideFromIndex(),

            BelongsTo::make('Query', 'citationQuery', CitationQuery::class)
                ->sortable(),

            BelongsTo::make('Check', 'citationCheck', CitationCheck::class)
                ->hideFromIndex(),

            Boolean::make('Read', 'is_read')
                ->sortable()
                ->filterable(),

            DateTime::make('Read At')
                ->hideFromIndex()
                ->exceptOnForms(),

            DateTime::make('Created At')
                ->sortable()
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
