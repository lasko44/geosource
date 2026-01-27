<?php

namespace App\Nova;

use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class LearningResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\LearningResource>
     */
    public static $model = \App\Models\LearningResource::class;

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
        'id', 'title', 'slug', 'category',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Learning Resources';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Learning Resource';
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
                    ->creationRules('unique:learning_resources,slug')
                    ->updateRules('unique:learning_resources,slug,{{resourceId}}'),

                Select::make('Category')
                    ->options(\App\Models\LearningResource::getCategories())
                    ->displayUsingLabels()
                    ->sortable()
                    ->filterable()
                    ->rules('required'),

                Select::make('Category Icon')
                    ->options(\App\Models\LearningResource::getCategoryIcons())
                    ->displayUsingLabels()
                    ->default('BookOpen')
                    ->rules('required'),

                Textarea::make('Excerpt')
                    ->help('Short description shown on the resources index page')
                    ->rows(2)
                    ->rules('required'),

                Textarea::make('Intro')
                    ->help('Optional intro paragraph shown below the title')
                    ->rows(2)
                    ->nullable(),
            ]),

            new Panel('Content', [
                Select::make('Content Type')
                    ->options([
                        'html' => 'HTML (Trix Editor)',
                        'blocks' => 'JSON Blocks',
                    ])
                    ->displayUsingLabels()
                    ->default('html')
                    ->help('HTML uses the Trix editor. Blocks use JSON for custom Vue components.')
                    ->rules('required'),

                Trix::make('Content')
                    ->help('Main article content (used when Content Type is HTML)')
                    ->rules('required_if:content_type,html')
                    ->alwaysShow(),

                Code::make('Content Blocks')
                    ->json()
                    ->help('Set Content Type to "JSON Blocks" to use this. <a href="/nova/documentation?search=block" target="_blank" class="text-primary-500 hover:underline">View block examples in documentation</a>')
                    ->nullable()
                    ->hideFromIndex(),
            ]),

            new Panel('SEO', [
                Text::make('Meta Title')
                    ->help('Leave empty to use the article title')
                    ->nullable()
                    ->hideFromIndex(),

                Textarea::make('Meta Description')
                    ->help('Description for search engines (150-160 characters recommended)')
                    ->rows(2)
                    ->nullable()
                    ->hideFromIndex(),

                Text::make('OG Title')
                    ->help('Title for social sharing. Leave empty to use meta title.')
                    ->nullable()
                    ->hideFromIndex(),

                Textarea::make('OG Description')
                    ->help('Description for social sharing')
                    ->rows(2)
                    ->nullable()
                    ->hideFromIndex(),

                Text::make('OG Image')
                    ->help('Full URL to social share image (1200x630px recommended). Leave empty for default.')
                    ->nullable()
                    ->hideFromIndex(),

                Text::make('Canonical URL')
                    ->help('Leave empty to use the default URL')
                    ->nullable()
                    ->hideFromIndex(),
            ]),

            new Panel('Structured Data', [
                Code::make('JSON-LD', 'json_ld')
                    ->json()
                    ->help('Custom JSON-LD structured data. Leave empty for auto-generated.')
                    ->nullable()
                    ->hideFromIndex(),

                Code::make('FAQ JSON-LD', 'faq_json_ld')
                    ->json()
                    ->help('FAQ structured data for rich snippets')
                    ->nullable()
                    ->hideFromIndex(),
            ]),

            new Panel('Navigation', [
                Text::make('Previous Slug', 'prev_slug')
                    ->help('Slug of the previous article in sequence')
                    ->nullable()
                    ->hideFromIndex(),

                Text::make('Previous Title', 'prev_title')
                    ->help('Title to display for previous link')
                    ->nullable()
                    ->hideFromIndex(),

                Text::make('Next Slug', 'next_slug')
                    ->help('Slug of the next article in sequence')
                    ->nullable()
                    ->hideFromIndex(),

                Text::make('Next Title', 'next_title')
                    ->help('Title to display for next link')
                    ->nullable()
                    ->hideFromIndex(),

                Code::make('Related Articles')
                    ->json()
                    ->help('Array of slugs for related articles, e.g. ["what-is-geo", "geo-vs-seo"]')
                    ->nullable()
                    ->hideFromIndex(),
            ]),

            new Panel('Display Settings', [
                Boolean::make('Featured', 'is_featured')
                    ->help('Show in the quick-access cards section')
                    ->sortable()
                    ->filterable(),

                Select::make('Featured Icon')
                    ->options(\App\Models\LearningResource::getCategoryIcons())
                    ->displayUsingLabels()
                    ->help('Icon to show on featured card')
                    ->nullable()
                    ->hideFromIndex(),

                Number::make('Sort Order')
                    ->help('Lower numbers appear first')
                    ->default(0)
                    ->sortable(),

                Boolean::make('Published', 'is_published')
                    ->sortable()
                    ->filterable(),

                DateTime::make('Published At')
                    ->help('Schedule publication. Leave empty to publish immediately when published.')
                    ->nullable()
                    ->sortable(),
            ]),

            new Panel('Timestamps', [
                DateTime::make('Created At')
                    ->exceptOnForms(),

                DateTime::make('Updated At')
                    ->exceptOnForms(),
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
