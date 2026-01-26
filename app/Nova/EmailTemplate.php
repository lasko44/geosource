<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class EmailTemplate extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\EmailTemplate>
     */
    public static $model = \App\Models\EmailTemplate::class;

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
        'id', 'name', 'slug', 'subject',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Email Templates';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Email Template';
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

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Slug')
                ->sortable()
                ->hideFromIndex()
                ->help('Leave blank to auto-generate from name'),

            Text::make('Subject')
                ->sortable()
                ->rules('required', 'max:255')
                ->help('Use {{user_name}}, {{app_name}} for variables'),

            Text::make('Preview Text')
                ->hideFromIndex()
                ->help('Optional preview text shown in email clients'),

            Select::make('Type')
                ->options([
                    'marketing' => 'Marketing',
                    'announcement' => 'Announcement',
                    'newsletter' => 'Newsletter',
                    'promotional' => 'Promotional',
                ])
                ->displayUsingLabels()
                ->sortable()
                ->filterable()
                ->rules('required'),

            Boolean::make('Active', 'is_active')
                ->sortable()
                ->filterable(),

            Trix::make('Content')
                ->rules('required')
                ->help('Available variables: {{user_name}}, {{user_email}}, {{user_first_name}}, {{app_name}}, {{app_url}}, {{unsubscribe_url}}, {{current_year}}'),

            HasMany::make('Campaigns', 'campaigns', EmailCampaign::class),
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
