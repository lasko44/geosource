<?php

namespace App\Nova;

use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class GeoStudyResult extends Resource
{
    public static $model = \App\Models\GeoStudyResult::class;

    public static $title = 'url';

    public static $search = ['id', 'uuid', 'url', 'title', 'domain'];

    public static function label(): string
    {
        return 'Study Results';
    }

    public static function singularLabel(): string
    {
        return 'Study Result';
    }

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('UUID')
                ->onlyOnDetail()
                ->copyable(),

            BelongsTo::make('Study', 'geoStudy', GeoStudy::class)
                ->sortable()
                ->filterable(),

            URL::make('URL')
                ->displayUsing(fn ($value) => strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value)
                ->sortable(),

            Text::make('Title')
                ->sortable()
                ->hideFromIndex(),

            Text::make('Domain')
                ->sortable()
                ->filterable(),

            Text::make('Category')
                ->sortable()
                ->filterable()
                ->nullable(),

            Number::make('Total Score')
                ->sortable()
                ->step(0.01),

            Badge::make('Grade')
                ->map([
                    'A+' => 'success',
                    'A' => 'success',
                    'A-' => 'success',
                    'B+' => 'info',
                    'B' => 'info',
                    'B-' => 'info',
                    'C+' => 'warning',
                    'C' => 'warning',
                    'C-' => 'warning',
                    'D+' => 'danger',
                    'D' => 'danger',
                    'D-' => 'danger',
                    'F' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            Number::make('Percentage')
                ->sortable()
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-')
                ->step(0.01),

            Badge::make('Status')
                ->map([
                    'pending' => 'warning',
                    'processing' => 'info',
                    'completed' => 'success',
                    'failed' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            new Panel('Pillar Scores', $this->pillarFields()),

            new Panel('Details', $this->detailFields()),
        ];
    }

    protected function pillarFields(): array
    {
        return [
            Number::make('Definitions', 'pillar_definitions')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Structure', 'pillar_structure')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Authority', 'pillar_authority')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Machine Readable', 'pillar_machine_readable')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Answerability', 'pillar_answerability')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('E-E-A-T', 'pillar_eeat')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Citations', 'pillar_citations')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('AI Accessibility', 'pillar_ai_accessibility')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Freshness', 'pillar_freshness')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Readability', 'pillar_readability')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Question Coverage', 'pillar_question_coverage')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),

            Number::make('Multimedia', 'pillar_multimedia')
                ->hideFromIndex()
                ->step(0.01)
                ->displayUsing(fn ($value) => $value ? $value . '%' : '-'),
        ];
    }

    protected function detailFields(): array
    {
        return [
            Text::make('Source Type')
                ->onlyOnDetail(),

            Code::make('Source Metadata')
                ->json()
                ->onlyOnDetail(),

            Code::make('Full Results')
                ->json()
                ->onlyOnDetail(),

            Text::make('Error Message')
                ->onlyOnDetail()
                ->nullable(),

            DateTime::make('Processed At')
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
        return [];
    }
}
