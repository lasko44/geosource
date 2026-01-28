<?php

namespace App\Nova;

use App\Nova\Actions\CancelGeoStudy;
use App\Nova\Actions\ExportGeoStudy;
use App\Nova\Actions\ImportUrlsToStudy;
use App\Nova\Actions\StartGeoStudy;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class GeoStudy extends Resource
{
    public static $model = \App\Models\GeoStudy::class;

    public static $title = 'name';

    public static $search = ['id', 'uuid', 'name', 'description'];

    public static function label(): string
    {
        return 'GEO Studies';
    }

    public static function singularLabel(): string
    {
        return 'GEO Study';
    }

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('UUID')
                ->onlyOnDetail()
                ->copyable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Textarea::make('Description')
                ->hideFromIndex()
                ->rows(3)
                ->nullable(),

            Select::make('Category')
                ->options(\App\Models\GeoStudy::categoryOptions())
                ->displayUsingLabels()
                ->sortable()
                ->filterable()
                ->rules('required'),

            Select::make('Source Type')
                ->options(\App\Models\GeoStudy::sourceTypeOptions())
                ->displayUsingLabels()
                ->sortable()
                ->filterable()
                ->rules('required'),

            Badge::make('Status')
                ->map([
                    'draft' => 'warning',
                    'collecting' => 'info',
                    'processing' => 'info',
                    'completed' => 'success',
                    'failed' => 'danger',
                    'cancelled' => 'warning',
                ])
                ->sortable()
                ->filterable(),

            BelongsTo::make('Created By', 'creator', User::class)
                ->nullable()
                ->sortable()
                ->filterable(),

            new Panel('Progress', $this->progressFields()),

            new Panel('Results Summary', $this->resultsFields()),

            new Panel('Aggregate Statistics', $this->aggregateFields()),

            new Panel('Timestamps', $this->timestampFields()),

            HasMany::make('Results', 'results', GeoStudyResult::class),
        ];
    }

    protected function progressFields(): array
    {
        return [
            Number::make('Total URLs')
                ->sortable()
                ->exceptOnForms(),

            Number::make('Processed URLs')
                ->exceptOnForms(),

            Number::make('Failed URLs')
                ->exceptOnForms(),

            Number::make('Progress %', 'progress_percent')
                ->exceptOnForms()
                ->displayUsing(function ($value) {
                    return $value . '%';
                }),

            Text::make('Batch ID')
                ->onlyOnDetail()
                ->copyable()
                ->nullable(),

            Text::make('Error Message')
                ->onlyOnDetail()
                ->nullable(),
        ];
    }

    protected function resultsFields(): array
    {
        return [
            Text::make('Average Score', function () {
                return $this->aggregate_stats['avg_score'] ?? '-';
            })->onlyOnDetail(),

            Text::make('Average Percentage', function () {
                $pct = $this->aggregate_stats['avg_percentage'] ?? null;
                return $pct ? $pct . '%' : '-';
            })->onlyOnDetail(),

            Text::make('Median Score', function () {
                return $this->aggregate_stats['median_score'] ?? '-';
            })->onlyOnDetail(),

            Code::make('Grade Distribution', function () {
                return $this->aggregate_stats['grade_distribution'] ?? [];
            })->json()->onlyOnDetail(),

            Code::make('Top Performers')
                ->json()
                ->onlyOnDetail(),

            Code::make('Bottom Performers')
                ->json()
                ->onlyOnDetail(),
        ];
    }

    protected function aggregateFields(): array
    {
        return [
            Code::make('Aggregate Stats')
                ->json()
                ->onlyOnDetail(),

            Code::make('Category Breakdown')
                ->json()
                ->onlyOnDetail(),

            Code::make('Pillar Analysis')
                ->json()
                ->onlyOnDetail(),

            Code::make('Source Config')
                ->json()
                ->onlyOnDetail(),
        ];
    }

    protected function timestampFields(): array
    {
        return [
            DateTime::make('Started At')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Completed At')
                ->sortable()
                ->exceptOnForms(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms()
                ->onlyOnDetail(),

            DateTime::make('Updated At')
                ->sortable()
                ->exceptOnForms()
                ->onlyOnDetail(),
        ];
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [];
    }

    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    public function actions(NovaRequest $request): array
    {
        return [
            ImportUrlsToStudy::make()
                ->showInline()
                ->confirmText('Paste URLs (one per line) or upload a CSV file to import URLs to this study.')
                ->confirmButtonText('Import URLs')
                ->cancelButtonText('Cancel'),

            StartGeoStudy::make()
                ->showInline()
                ->confirmText('This will begin processing all imported URLs. This action cannot be undone.')
                ->confirmButtonText('Start Study')
                ->cancelButtonText('Cancel'),

            CancelGeoStudy::make()
                ->showInline()
                ->confirmText('This will stop the study and cancel all pending jobs. Completed results will be preserved.')
                ->confirmButtonText('Cancel Study')
                ->cancelButtonText('Keep Running'),

            ExportGeoStudy::make()
                ->showInline()
                ->confirmButtonText('Export')
                ->cancelButtonText('Cancel'),
        ];
    }
}
