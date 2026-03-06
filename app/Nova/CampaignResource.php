<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\Campaign;

class CampaignResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Campaign::class;

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
        'id', 'name', 'message',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Select::make('Type')
                ->options([
                    'text' => 'Text Message',
                    'template' => 'Template Message',
                    'media' => 'Media Message',
                ])
                ->rules('required')
                ->sortable(),

            Textarea::make('Message')
                ->rows(3)
                ->rules('required', 'max:1000'),

            Select::make('Status')
                ->options([
                    'draft' => 'Draft',
                    'scheduled' => 'Scheduled',
                    'sending' => 'Sending',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->readonly(function ($request) {
                    return !$request->isCreateOrUpdateRequest();
                })
                ->sortable()
                ->rules('required'),

            Number::make('Total Contacts')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            Number::make('Sent Count')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            Number::make('Delivered Count')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            Number::make('Read Count')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            Number::make('Failed Count')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            DateTime::make('Scheduled At')
                ->sortable()
                ->rules('nullable', 'after:now')
                ->hideWhenCreating(function ($request) {
                    return !$request->user()->hasPermissionTo('schedule-campaigns');
                }),

            DateTime::make('Started At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            DateTime::make('Completed At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            BelongsTo::make('Created By', 'createdBy', 'App\Nova\UserResource')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            BelongsToMany::make('Target Tags', 'tags', 'App\Nova\TagResource'),

            HasMany::make('Messages'),

            DateTime::make('Created At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            DateTime::make('Updated At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [
            new Metrics\CampaignsByStatus,
            new Metrics\TotalCampaigns,
            new Metrics\CampaignDeliveryRate,
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new Filters\CampaignStatus,
            new Filters\CampaignType,
            new Filters\CampaignDateRange,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [
            new Lenses\ActiveCampaigns,
            new Lenses\CompletedCampaigns,
            new Lenses\FailedCampaigns,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            new Actions\SendCampaign,
            new Actions\CancelCampaign,
            new Actions\DuplicateCampaign,
            new Actions\ExportCampaignReport,
        ];
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Campaigns';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'Campaign';
    }

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return 'Campaign Management';
    }

    /**
     * Determine if this resource is available for navigation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function availableForNavigation(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('manage-campaigns');
    }

    /**
     * Determine if this resource can be viewed.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function viewable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('view-campaigns');
    }

    /**
     * Determine if this resource can be created.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function creatable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('create-campaigns');
    }

    /**
     * Determine if this resource can be updated.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function updatable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('edit-campaigns');
    }

    /**
     * Determine if this resource can be deleted.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function deletable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('delete-campaigns');
    }
}
