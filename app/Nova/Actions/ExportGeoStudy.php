<?php

namespace App\Nova\Actions;

use App\Models\GeoStudy;
use App\Services\GeoStudy\GeoStudyExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class ExportGeoStudy extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Export Results';

    public function handle(ActionFields $fields, Collection $models)
    {
        $exportService = app(GeoStudyExportService::class);
        $format = $fields->get('format') ?? 'csv';

        /** @var GeoStudy $study */
        $study = $models->first();

        if (! $study) {
            return Action::danger('No study selected');
        }

        if (! $study->isCompleted()) {
            return Action::danger('Can only export completed studies');
        }

        if ($study->results()->where('status', 'completed')->count() === 0) {
            return Action::danger('No completed results to export');
        }

        try {
            if ($format === 'csv') {
                $content = $exportService->exportToCsv($study);
                $filename = "geo-study-{$study->uuid}.csv";
                $mimeType = 'text/csv';
            } elseif ($format === 'json') {
                $content = json_encode($exportService->exportToJson($study), JSON_PRETTY_PRINT);
                $filename = "geo-study-{$study->uuid}.json";
                $mimeType = 'application/json';
            } else {
                $content = json_encode($exportService->exportForBlog($study), JSON_PRETTY_PRINT);
                $filename = "geo-study-{$study->uuid}-blog.json";
                $mimeType = 'application/json';
            }

            return Action::download($content, $filename);
        } catch (\Exception $e) {
            return Action::danger('Export failed: ' . $e->getMessage());
        }
    }

    public function fields(NovaRequest $request)
    {
        return [
            Select::make('Format')
                ->options([
                    'csv' => 'CSV (Results Table)',
                    'json' => 'JSON (Full Data)',
                    'blog' => 'JSON (Blog Embed Format)',
                ])
                ->default('csv')
                ->rules('required'),
        ];
    }
}
