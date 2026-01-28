<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class PageView extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\PageView>
     */
    public static $model = \App\Models\PageView::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'path';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'path', 'url', 'referrer_host', 'utm_source', 'country', 'city',
    ];


    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Page Views';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Page View';
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

            Badge::make('Page Type', 'page_type')
                ->map([
                    'home' => 'success',
                    'blog' => 'info',
                    'scan' => 'warning',
                    'dashboard' => 'success',
                    'pricing' => 'info',
                    'login' => 'warning',
                    'register' => 'warning',
                ])
                ->sortable()
                ->filterable(),

            Text::make('Path')
                ->sortable()
                ->displayUsing(fn ($value) => strlen($value) > 40 ? substr($value, 0, 40) . '...' : $value),

            URL::make('Full URL', 'url')
                ->displayUsing(fn ($value) => strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value)
                ->onlyOnDetail(),

            Text::make('Page Title')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('User ID', 'user_id')
                ->displayUsing(fn ($value) => $value ?? '-')
                ->sortable(),

            // Referrer info
            Text::make('Referrer', 'referrer_host')
                ->sortable()
                ->filterable()
                ->nullable(),

            URL::make('Full Referrer', 'referrer')
                ->onlyOnDetail()
                ->nullable(),

            // UTM Parameters
            Text::make('UTM Source', 'utm_source')
                ->sortable()
                ->filterable()
                ->nullable(),

            Text::make('UTM Medium', 'utm_medium')
                ->hideFromIndex()
                ->nullable(),

            Text::make('UTM Campaign', 'utm_campaign')
                ->hideFromIndex()
                ->nullable(),

            Text::make('UTM Term', 'utm_term')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('UTM Content', 'utm_content')
                ->onlyOnDetail()
                ->nullable(),

            // Geographic info
            Text::make('Country')
                ->sortable()
                ->filterable()
                ->nullable(),

            Text::make('Region')
                ->hideFromIndex()
                ->nullable(),

            Text::make('City')
                ->hideFromIndex()
                ->nullable(),

            // Device info
            Badge::make('Device', 'device_type')
                ->map([
                    'desktop' => 'success',
                    'mobile' => 'info',
                    'tablet' => 'warning',
                ])
                ->sortable()
                ->filterable(),

            Text::make('Browser')
                ->sortable()
                ->filterable()
                ->nullable(),

            Text::make('Browser Version')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('OS')
                ->sortable()
                ->filterable()
                ->nullable(),

            Text::make('OS Version')
                ->onlyOnDetail()
                ->nullable(),

            // Technical info
            Boolean::make('Is Bot', 'is_bot')
                ->sortable()
                ->filterable(),

            Text::make('IP Address')
                ->onlyOnDetail()
                ->nullable(),

            Text::make('Session ID', 'session_id')
                ->onlyOnDetail(),

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
        // Only show metrics if the table has data
        try {
            if (\App\Models\PageView::count() === 0) {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }

        return [
            (new \App\Nova\Metrics\PageViewsPerDay)->width('1/3'),
            (new \App\Nova\Metrics\UniqueVisitorsPerDay)->width('1/3'),
            (new \App\Nova\Metrics\TopReferrers)->width('1/3'),
            (new \App\Nova\Metrics\TopCountries)->width('1/2'),
            (new \App\Nova\Metrics\DeviceBreakdown)->width('1/2'),
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
