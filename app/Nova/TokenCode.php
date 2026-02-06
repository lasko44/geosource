<?php

namespace App\Nova;

use App\Models\TokenCode as TokenCodeModel;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class TokenCode extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TokenCode>
     */
    public static $model = \App\Models\TokenCode::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'code';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'code', 'description',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Billing';

    /**
     * The default ordering for the resource.
     *
     * @var array
     */
    public static $indexDefaultOrder = [
        'created_at' => 'desc',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Code')
                ->sortable()
                ->rules('required', 'max:50')
                ->creationRules('unique:token_codes,code')
                ->updateRules('unique:token_codes,code,{{resourceId}}')
                ->help('Leave blank to auto-generate, or enter a custom code (e.g., WELCOME20)')
                ->default(fn () => TokenCodeModel::generateCode()),

            Select::make('Type')
                ->options([
                    TokenCodeModel::TYPE_PROMO => 'Promo Code (Multi-use)',
                    TokenCodeModel::TYPE_SINGLE => 'Single Use Code',
                ])
                ->displayUsingLabels()
                ->rules('required')
                ->default(TokenCodeModel::TYPE_PROMO)
                ->filterable()
                ->help('Promo codes can be used by multiple users. Single-use codes are one-time only.'),

            Number::make('Tokens')
                ->sortable()
                ->rules('required', 'integer', 'min:1')
                ->help('Number of tokens to give when redeemed'),

            Text::make('Description')
                ->nullable()
                ->hideFromIndex()
                ->help('Internal note about this code'),

            Number::make('Max Uses', 'max_uses')
                ->nullable()
                ->min(1)
                ->help('Leave blank for unlimited uses (promo codes only). Single-use codes default to 1.'),

            Number::make('Uses', 'uses_count')
                ->sortable()
                ->exceptOnForms(),

            Text::make('Remaining', function () {
                if ($this->max_uses === null) {
                    return '∞';
                }

                return max(0, $this->max_uses - $this->uses_count);
            })->onlyOnIndex(),

            DateTime::make('Expires At', 'expires_at')
                ->nullable()
                ->sortable()
                ->help('Leave blank for no expiration'),

            Boolean::make('Active', 'is_active')
                ->sortable()
                ->filterable()
                ->default(true),

            Badge::make('Status', function () {
                if (! $this->is_active) {
                    return 'Inactive';
                }
                if ($this->expires_at && $this->expires_at->isPast()) {
                    return 'Expired';
                }
                if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
                    return 'Exhausted';
                }

                return 'Active';
            })->map([
                'Active' => 'success',
                'Inactive' => 'danger',
                'Expired' => 'warning',
                'Exhausted' => 'info',
            ])->onlyOnIndex(),

            BelongsTo::make('Created By', 'creator', User::class)
                ->nullable()
                ->exceptOnForms(),

            DateTime::make('Created At')
                ->sortable()
                ->exceptOnForms(),

            HasMany::make('Redemptions', 'redemptions', TokenCodeRedemption::class),
        ];
    }

    /**
     * Get the cards available for the request.
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
            Actions\GenerateSingleUseCodes::make()
                ->confirmText('Enter the number of single-use codes to generate and the token amount for each.')
                ->confirmButtonText('Generate Codes')
                ->cancelButtonText('Cancel')
                ->standalone(),
        ];
    }

    /**
     * Perform any actions after the resource is created.
     */
    public static function afterCreate(NovaRequest $request, $model): void
    {
        // Set created_by to current user
        $model->update(['created_by' => $request->user()->id]);

        // For single-use codes, default max_uses to 1 if not set
        if ($model->type === TokenCodeModel::TYPE_SINGLE && $model->max_uses === null) {
            $model->update(['max_uses' => 1]);
        }
    }
}
