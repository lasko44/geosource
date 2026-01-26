<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class CitationCheck extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CitationCheck>
     */
    public static $model = \App\Models\CitationCheck::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'uuid';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'uuid', 'platform',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Citation Checks';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Citation Check';
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

            BelongsTo::make('Query', 'citationQuery', CitationQuery::class)
                ->sortable(),

            BelongsTo::make('User')
                ->sortable()
                ->filterable(),

            BelongsTo::make('Team')
                ->nullable()
                ->hideFromIndex(),

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

            Badge::make('Status')
                ->map([
                    'pending' => 'warning',
                    'processing' => 'info',
                    'completed' => 'success',
                    'failed' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            Boolean::make('Cited', 'is_cited')
                ->sortable()
                ->filterable(),

            Text::make('Progress', function () {
                if ($this->status === 'completed' || $this->status === 'failed') {
                    return '100%';
                }
                return ($this->progress_percent ?? 0) . '%';
            })->exceptOnForms()->hideFromIndex(),

            Text::make('Progress Step')
                ->hideFromIndex()
                ->exceptOnForms(),

            Textarea::make('AI Response')
                ->hideFromIndex()
                ->exceptOnForms()
                ->rows(10),

            Code::make('Citations')
                ->json()
                ->hideFromIndex()
                ->exceptOnForms(),

            Code::make('Metadata')
                ->json()
                ->hideFromIndex()
                ->exceptOnForms(),

            Text::make('Error Message')
                ->hideFromIndex()
                ->exceptOnForms(),

            Text::make('Duration', function () {
                $duration = $this->duration;
                return $duration ? $duration . 's' : '-';
            })->exceptOnForms()->hideFromIndex(),

            DateTime::make('Started At')
                ->sortable()
                ->exceptOnForms()
                ->hideFromIndex(),

            DateTime::make('Completed At')
                ->sortable()
                ->exceptOnForms()
                ->hideFromIndex(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms(),

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
