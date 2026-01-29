<?php

namespace App\Nova\Actions;

use App\Models\TokenCode;
use App\Services\TokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class GenerateSingleUseCodes extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Generate Single-Use Codes';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $tokenService = app(TokenService::class);

        $codes = $tokenService->generateSingleUseCodes(
            count: (int) $fields->count,
            tokens: (int) $fields->tokens,
            description: $fields->description,
            expiresAt: $fields->expires_at ? new \DateTime($fields->expires_at) : null,
            createdBy: request()->user()
        );

        // Format codes for display/download
        $codeList = collect($codes)->pluck('code')->implode("\n");

        return Action::download(
            $this->generateCsv($codes),
            'token-codes-' . now()->format('Y-m-d-His') . '.csv'
        );
    }

    /**
     * Generate CSV content from codes.
     */
    protected function generateCsv(array $codes): string
    {
        $csv = "Code,Tokens,Type,Expires At,Created At\n";

        foreach ($codes as $code) {
            $csv .= sprintf(
                "%s,%d,%s,%s,%s\n",
                $code->code,
                $code->tokens,
                $code->type,
                $code->expires_at?->toDateTimeString() ?? 'Never',
                $code->created_at->toDateTimeString()
            );
        }

        return $csv;
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Number::make('Number of Codes', 'count')
                ->rules('required', 'integer', 'min:1', 'max:100')
                ->default(10)
                ->help('How many codes to generate (max 100)'),

            Number::make('Tokens Per Code', 'tokens')
                ->rules('required', 'integer', 'min:1')
                ->default(20)
                ->help('Number of tokens each code will give'),

            Text::make('Description')
                ->nullable()
                ->help('Internal note about these codes'),

            DateTime::make('Expires At', 'expires_at')
                ->nullable()
                ->help('Leave blank for no expiration'),
        ];
    }
}
