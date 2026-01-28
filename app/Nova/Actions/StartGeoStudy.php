<?php

namespace App\Nova\Actions;

use App\Models\GeoStudy;
use App\Services\GeoStudy\GeoStudyService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class StartGeoStudy extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Start Study';

    public function handle(ActionFields $fields, Collection $models)
    {
        $studyService = app(GeoStudyService::class);
        $started = 0;
        $errors = [];

        foreach ($models as $study) {
            /** @var GeoStudy $study */

            if (! $study->isDraft()) {
                $errors[] = "{$study->name}: Can only start studies in draft status";
                continue;
            }

            if ($study->urls()->count() === 0) {
                $errors[] = "{$study->name}: No URLs imported";
                continue;
            }

            if ($studyService->start($study)) {
                $started++;
            } else {
                $errors[] = "{$study->name}: Failed to start";
            }
        }

        if (! empty($errors)) {
            return Action::danger(implode("\n", $errors));
        }

        return Action::message($started === 1
            ? 'Study has been started! Processing will begin shortly.'
            : "{$started} studies have been started!");
    }

    public function fields(NovaRequest $request)
    {
        return [];
    }
}
