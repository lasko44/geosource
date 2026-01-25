<?php

namespace App\Nova;

use Illuminate\Support\Str;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class FailedJob extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string
     */
    public static $model = \App\Models\FailedJob::class;

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
        'id', 'uuid', 'queue', 'connection',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Failed Jobs';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Failed Job';
    }

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

            Text::make('UUID')
                ->copyable()
                ->onlyOnDetail(),

            Text::make('Connection')
                ->sortable(),

            Text::make('Queue')
                ->sortable(),

            Text::make('Job', function () {
                $payload = json_decode($this->payload ?? '{}', true);

                return $payload['displayName'] ?? 'Unknown';
            }),

            Text::make('Job Class', function () {
                $payload = json_decode($this->payload ?? '{}', true);
                $displayName = $payload['displayName'] ?? '';

                return Str::afterLast($displayName, '\\');
            })->onlyOnIndex(),

            DateTime::make('Failed At', 'failed_at')
                ->sortable(),

            Text::make('Exception Summary', function () {
                $lines = explode("\n", $this->exception ?? '');

                return \Illuminate\Support\Str::limit($lines[0] ?? 'No exception', 80);
            })->onlyOnIndex(),

            Code::make('Payload')
                ->json()
                ->onlyOnDetail(),

            Code::make('Exception')
                ->language('text')
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
        return [
            Actions\RetryFailedJob::make()
                ->showInline()
                ->confirmText('This will retry the failed job. Continue?')
                ->confirmButtonText('Retry')
                ->cancelButtonText('Cancel'),

            Actions\DeleteFailedJob::make()
                ->showInline()
                ->confirmText('This will permanently delete this failed job record. Continue?')
                ->confirmButtonText('Delete')
                ->cancelButtonText('Cancel'),
        ];
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
