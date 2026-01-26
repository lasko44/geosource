<?php

namespace App\Nova;

use App\Nova\Actions\SendEmailCampaign;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class EmailCampaign extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\EmailCampaign>
     */
    public static $model = \App\Models\EmailCampaign::class;

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
        'id', 'name',
    ];

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return 'Email Campaigns';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return 'Email Campaign';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            BelongsTo::make('Template', 'template', EmailTemplate::class)
                ->sortable()
                ->rules('required'),

            Select::make('Audience')
                ->options([
                    'all' => 'All Users',
                    'free' => 'Free Users',
                    'pro' => 'Pro Users',
                    'agency' => 'Agency Users',
                    'custom' => 'Custom',
                ])
                ->displayUsingLabels()
                ->sortable()
                ->filterable()
                ->rules('required'),

            Badge::make('Status')
                ->map([
                    'draft' => 'info',
                    'scheduled' => 'warning',
                    'sending' => 'info',
                    'sent' => 'success',
                    'cancelled' => 'danger',
                ])
                ->sortable()
                ->filterable(),

            Text::make('Recipients', function () {
                if ($this->status === 'draft') {
                    return $this->getRecipientCount() . ' (estimated)';
                }
                return $this->total_recipients;
            })->exceptOnForms(),

            DateTime::make('Scheduled At')
                ->sortable()
                ->nullable(),

            BelongsTo::make('Created By', 'creator', User::class)
                ->exceptOnForms()
                ->nullable(),

            new Panel('Statistics', $this->statisticsFields()),

            HasMany::make('Sends', 'sends', EmailCampaignSend::class),
        ];
    }

    /**
     * Get the statistics fields for the resource.
     */
    protected function statisticsFields(): array
    {
        return [
            Number::make('Total Recipients')
                ->exceptOnForms()
                ->hideFromIndex(),

            Number::make('Sent', 'sent_count')
                ->exceptOnForms()
                ->hideFromIndex(),

            Number::make('Failed', 'failed_count')
                ->exceptOnForms()
                ->hideFromIndex(),

            Number::make('Opened', 'opened_count')
                ->exceptOnForms()
                ->hideFromIndex(),

            Number::make('Clicked', 'clicked_count')
                ->exceptOnForms()
                ->hideFromIndex(),

            Text::make('Progress', function () {
                return $this->getProgressPercentage() . '%';
            })->exceptOnForms()->hideFromIndex(),

            Text::make('Open Rate', function () {
                return $this->getOpenRate() . '%';
            })->exceptOnForms()->hideFromIndex(),

            Text::make('Click Rate', function () {
                return $this->getClickRate() . '%';
            })->exceptOnForms()->hideFromIndex(),

            DateTime::make('Sent At')
                ->exceptOnForms()
                ->hideFromIndex(),
        ];
    }

    /**
     * Get the cards available for the resource.
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
            SendEmailCampaign::make()
                ->showInline()
                ->confirmText('Are you sure you want to send this campaign? This action cannot be undone.')
                ->confirmButtonText('Send Campaign')
                ->cancelButtonText('Cancel'),
        ];
    }
}
