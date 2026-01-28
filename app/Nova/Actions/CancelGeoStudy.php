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

class CancelGeoStudy extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Cancel Study';

    public $confirmText = 'Are you sure you want to cancel this study? Completed results will be preserved.';

    public function handle(ActionFields $fields, Collection $models)
    {
        $studyService = app(GeoStudyService::class);
        $cancelled = 0;
        $errors = [];

        foreach ($models as $study) {
            /** @var GeoStudy $study */

            if (! $study->canCancel()) {
                $errors[] = "{$study->name}: Can only cancel studies that are in progress";
                continue;
            }

            if ($studyService->cancel($study)) {
                $cancelled++;
            } else {
                $errors[] = "{$study->name}: Failed to cancel";
            }
        }

        if (! empty($errors)) {
            return Action::danger(implode("\n", $errors));
        }

        return Action::message($cancelled === 1
            ? 'Study has been cancelled.'
            : "{$cancelled} studies have been cancelled.");
    }

    public function fields(NovaRequest $request)
    {
        return [];
    }
}
