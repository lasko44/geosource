<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class BlogShare extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\BlogShare>
     */
    public static $model = \App\Models\BlogShare::class;

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
        'platform', 'country', 'ip_address',
    ];

    /**
     * The default ordering for the resource.
     */
    public static $sort = ['created_at' => 'desc'];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Blog Shares';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Blog Share';
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

            DateTime::make('Time', 'created_at')
                ->sortable()
                ->filterable(),

            BelongsTo::make('Blog Post', 'blogPost', BlogPost::class)
                ->sortable()
                ->filterable(),

            Badge::make('Platform')
                ->map([
                    'twitter' => 'info',
                    'linkedin' => 'info',
                    'facebook' => 'info',
                    'copy_link' => 'success',
                ])
                ->labels([
                    'twitter' => 'X (Twitter)',
                    'linkedin' => 'LinkedIn',
                    'facebook' => 'Facebook',
                    'copy_link' => 'Copy Link',
                ])
                ->sortable()
                ->filterable(),

            Text::make('User', function () {
                return $this->user?->name ?? '-';
            })->sortable(),

            Text::make('Country')
                ->sortable()
                ->filterable()
                ->nullable(),

            Text::make('IP Address')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('Referrer')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('Visitor Hash', 'visitor_hash')
                ->onlyOnDetail(),

            Text::make('User Agent')
                ->onlyOnDetail()
                ->nullable(),
        ];
    }

    /**
     * Get the cards available for the resource.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            (new \App\Nova\Metrics\BlogSharesPerDay)->width('1/2'),
            (new \App\Nova\Metrics\SharesByPlatform)->width('1/2'),
        ];
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
     * Determine if the resource should be available for the given request.
     */
    public static function authorizedToCreate(\Illuminate\Http\Request $request): bool
    {
        return false; // Don't allow manual creation
    }

    /**
     * Determine if the current user can update the given resource.
     */
    public function authorizedToUpdate(\Illuminate\Http\Request $request): bool
    {
        return false; // Don't allow editing
    }

    /**
     * Determine if the current user can delete the given resource.
     */
    public function authorizedToDelete(\Illuminate\Http\Request $request): bool
    {
        return true; // Allow cleanup
    }
}
