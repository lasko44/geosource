<?php

namespace App\Nova\Actions;

use App\Services\TokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class GrantBonusTokens extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Grant Bonus Tokens';

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
        $amount = (int) $fields->amount;
        $reason = $fields->reason ?: 'Admin bonus';

        foreach ($models as $user) {
            $tokenService->creditBonus(
                $user,
                $amount,
                $reason
            );
        }

        return Action::message("Granted {$amount} tokens to " . $models->count() . " user(s).");
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
            Number::make('Amount')
                ->rules('required', 'integer', 'min:1')
                ->help('Number of tokens to grant'),

            Text::make('Reason')
                ->rules('nullable', 'max:255')
                ->help('Reason for the bonus (shown in transaction history)'),
        ];
    }
}
