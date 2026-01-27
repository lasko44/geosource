<?php

namespace App\Nova;

use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class Documentation extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Documentation>
     */
    public static $model = \App\Models\Documentation::class;

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
        'id', 'title', 'category', 'content',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Documentation';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Documentation';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            new Panel('Basic Information', [
                Text::make('Title')
                    ->sortable()
                    ->rules('required', 'max:255'),

                Slug::make('Slug')
                    ->from('Title')
                    ->rules('required', 'max:255')
                    ->creationRules('unique:documentations,slug')
                    ->updateRules('unique:documentations,slug,{{resourceId}}'),

                Select::make('Category')
                    ->options(\App\Models\Documentation::getCategories())
                    ->displayUsingLabels()
                    ->sortable()
                    ->filterable()
                    ->rules('required'),

                Textarea::make('Excerpt')
                    ->help('Brief summary shown in search results')
                    ->rows(2)
                    ->nullable(),
            ]),

            new Panel('Content', [
                Trix::make('Content')
                    ->help('Documentation content with HTML support. Use pre/code for code blocks.')
                    ->rules('required')
                    ->alwaysShow(),
            ]),

            new Panel('Settings', [
                Number::make('Sort Order')
                    ->help('Lower numbers appear first within category')
                    ->default(0)
                    ->sortable(),

                Boolean::make('Published', 'is_published')
                    ->default(true)
                    ->sortable()
                    ->filterable(),
            ]),
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
