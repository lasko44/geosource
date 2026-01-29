<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class TokenPackage extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TokenPackage>
     */
    public static $model = \App\Models\TokenPackage::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'tokens',
    ];

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Billing';

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Number::make('Tokens')
                ->sortable()
                ->rules('required', 'integer', 'min:1')
                ->help('Number of tokens in this package'),

            Number::make('Price (cents)', 'price_cents')
                ->sortable()
                ->rules('required', 'integer', 'min:0')
                ->help('Price in cents (e.g., 500 = $5.00)'),

            Currency::make('Price', function () {
                return $this->price_cents / 100;
            })->onlyOnIndex()->sortable(),

            Text::make('Stripe Price ID', 'stripe_price_id')
                ->rules('nullable', 'max:255')
                ->help('The price_xxx ID from Stripe. Required for purchases to work.'),

            Badge::make('Stripe Status', function () {
                return $this->stripe_price_id ? 'Connected' : 'Not Set';
            })->map([
                'Connected' => 'success',
                'Not Set' => 'danger',
            ])->onlyOnIndex(),

            Number::make('Savings %', 'savings_percent')
                ->sortable()
                ->rules('nullable', 'integer', 'min:0', 'max:100')
                ->help('Discount percentage to display (e.g., 25 for "Save 25%")'),

            Boolean::make('Popular', 'is_popular')
                ->sortable()
                ->filterable()
                ->help('Show "Most Popular" badge'),

            Boolean::make('Active', 'is_active')
                ->sortable()
                ->filterable()
                ->help('Only active packages are shown to users'),

            Number::make('Sort Order', 'sort_order')
                ->sortable()
                ->rules('required', 'integer')
                ->help('Lower numbers appear first'),

            Text::make('Price Per Token', function () {
                if ($this->tokens > 0) {
                    return '$' . number_format($this->price_cents / $this->tokens / 100, 3);
                }
                return '-';
            })->onlyOnIndex(),
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
        return [];
    }
}
