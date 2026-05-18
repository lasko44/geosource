<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Nova resource for viewing and managing A/B experiments.
 */
class Experiment extends Resource
{
    public static $model = \App\Models\Experiment::class;

    public static $title = 'name';

    public static $search = ['id', 'name'];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        $stats = [];
        if ($this->resource->exists) {
            $stats = app(\App\Services\ExperimentService::class)->getStats($this->resource);
        }

        $fields = [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Textarea::make('Description')
                ->nullable()
                ->hideFromIndex(),

            Badge::make('Status')
                ->map([
                    'draft' => 'info',
                    'running' => 'success',
                    'paused' => 'warning',
                    'completed' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            Code::make('Variants')
                ->json()
                ->hideFromIndex(),

            Text::make('Winning Variant')
                ->nullable()
                ->hideFromIndex(),

            Number::make('Participants', function () {
                return $this->participants()->count();
            })->onlyOnIndex(),

            Number::make('Conversions', function () {
                return $this->participants()->whereNotNull('converted_at')->count();
            })->onlyOnIndex(),
        ];

        // Add per-variant stats on detail view
        if ($this->resource->exists && ! empty($stats)) {
            foreach ($stats as $variant => $data) {
                $fields[] = Text::make(ucfirst($variant).' Rate', function () use ($data) {
                    return "{$data['conversions']}/{$data['total']} ({$data['rate']}%)";
                })->onlyOnDetail();
            }
        }

        $fields[] = DateTime::make('Started At')
            ->sortable()
            ->hideFromIndex();

        $fields[] = DateTime::make('Ended At')
            ->nullable()
            ->hideFromIndex();

        $fields[] = DateTime::make('Created At')
            ->sortable()
            ->exceptOnForms();

        $fields[] = HasMany::make('Participants', 'participants', ExperimentParticipant::class);

        return $fields;
    }

    /**
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            new \App\Nova\Metrics\ExperimentConversionRate,
            new \App\Nova\Metrics\ExperimentParticipantsPerDay,
        ];
    }

    /**
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
