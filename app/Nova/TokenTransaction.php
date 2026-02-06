<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class TokenTransaction extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TokenTransaction>
     */
    public static $model = \App\Models\TokenTransaction::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'uuid';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'uuid', 'description',
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
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('UUID', 'uuid')
                ->onlyOnDetail(),

            BelongsTo::make('User')
                ->sortable()
                ->searchable(),

            Badge::make('Type')
                ->map([
                    'purchase' => 'success',
                    'spend' => 'warning',
                    'refund' => 'info',
                    'bonus' => 'success',
                ])
                ->sortable()
                ->filterable(),

            Number::make('Amount', function () {
                $prefix = $this->amount >= 0 ? '+' : '';

                return $prefix.$this->amount;
            })->sortable(),

            Badge::make('Direction', function () {
                return $this->amount >= 0 ? 'Credit' : 'Debit';
            })->map([
                'Credit' => 'success',
                'Debit' => 'danger',
            ])->onlyOnIndex(),

            Number::make('Balance After', 'balance_after')
                ->sortable(),

            Text::make('Description')
                ->sortable(),

            DateTime::make('Created At')
                ->sortable(),
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

    /**
     * Determine if the resource can be created.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return false; // Transactions are created programmatically only
    }

    /**
     * Determine if the resource can be updated.
     */
    public function authorizedToUpdate(Request $request): bool
    {
        return false; // Transactions should not be edited
    }

    /**
     * Determine if the resource can be deleted.
     */
    public function authorizedToDelete(Request $request): bool
    {
        return false; // Transactions should not be deleted
    }
}
