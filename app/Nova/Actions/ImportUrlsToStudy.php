<?php

namespace App\Nova\Actions;

use App\Models\GeoStudy;
use App\Services\GeoStudy\GeoStudyService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class ImportUrlsToStudy extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Import URLs';

    public function handle(ActionFields $fields, Collection $models)
    {
        $studyService = app(GeoStudyService::class);
        $importType = $fields->get('import_type') ?? 'paste';

        /** @var GeoStudy $study */
        $study = $models->first();

        if (! $study) {
            return Action::danger('No study selected');
        }

        if (! $study->isDraft()) {
            return Action::danger('Can only import URLs to draft studies');
        }

        try {
            $imported = 0;

            if ($importType === 'paste') {
                $urlText = $fields->get('urls') ?? '';
                if (empty(trim($urlText))) {
                    return Action::danger('No URLs provided');
                }
                $imported = $studyService->importUrlsFromCsv($study, $urlText);
            } elseif ($importType === 'file') {
                $file = $fields->get('file');
                if (! $file) {
                    return Action::danger('No file uploaded');
                }
                $content = file_get_contents($file->getPathname());
                $imported = $studyService->importUrlsFromCsv($study, $content);
            } elseif ($importType === 'serp') {
                $keywords = $fields->get('keywords') ?? '';
                if (empty(trim($keywords))) {
                    return Action::danger('No keywords provided');
                }
                $keywordList = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $keywords)));
                $perKeyword = (int) ($fields->get('per_keyword') ?? 10);
                $imported = $studyService->importUrlsFromSerp($study, $keywordList, $perKeyword);
            } elseif ($importType === 'sitemap') {
                $sitemapUrl = $fields->get('sitemap_url') ?? '';
                if (empty(trim($sitemapUrl))) {
                    return Action::danger('No sitemap URL provided');
                }
                $limit = (int) ($fields->get('sitemap_limit') ?? 500);
                $imported = $studyService->importUrlsFromSitemap($study, $sitemapUrl, $limit);
            }

            if ($imported === 0) {
                return Action::danger('No valid URLs were imported. Check the format and try again.');
            }

            return Action::message("Successfully imported {$imported} URLs. Total: {$study->fresh()->total_urls}");
        } catch (\Exception $e) {
            return Action::danger('Import failed: ' . $e->getMessage());
        }
    }

    public function fields(NovaRequest $request)
    {
        return [
            Select::make('Import Type')
                ->options([
                    'paste' => 'Paste URLs',
                    'file' => 'Upload CSV File',
                    'serp' => 'SERP API (Keywords)',
                    'sitemap' => 'Sitemap URL',
                ])
                ->default('paste')
                ->rules('required'),

            Textarea::make('URLs')
                ->help('Paste URLs, one per line')
                ->rows(10)
                ->nullable(),

            File::make('File')
                ->help('Upload a CSV file with URLs')
                ->nullable(),

            Textarea::make('Keywords')
                ->help('Enter keywords to search (one per line or comma-separated)')
                ->rows(5)
                ->nullable(),

            Number::make('Results Per Keyword', 'per_keyword')
                ->help('Number of results to collect per keyword (max 10)')
                ->default(10)
                ->min(1)
                ->max(10)
                ->nullable(),

            Text::make('Sitemap URL')
                ->help('Enter the sitemap XML URL (e.g., https://example.com/sitemap.xml)')
                ->nullable(),

            Number::make('Sitemap Limit', 'sitemap_limit')
                ->help('Maximum number of URLs to import from sitemap')
                ->default(500)
                ->min(1)
                ->max(2000)
                ->nullable(),
        ];
    }
}
