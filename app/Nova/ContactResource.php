<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Email;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\Contact;

class ContactResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Contact::class;

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
        'id', 'name', 'phone', 'email',
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

            Text::make('Phone')
                ->sortable()
                ->rules('required', 'max:20')
                ->creationRules('unique:contacts,phone')
                ->updateRules('unique:contacts,phone,{{resourceId}}'),

            Email::make('Email')
                ->sortable()
                ->rules('nullable', 'email', 'max:255'),

            Select::make('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'blocked' => 'Blocked',
                ])
                ->default('active')
                ->sortable()
                ->rules('required'),

            BelongsToMany::make('Tags'),

            Textarea::make('Notes')
                ->rows(3)
                ->rules('nullable', 'max:1000'),

            DateTime::make('Last Message At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            DateTime::make('Created At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            DateTime::make('Updated At')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            HasMany::make('Messages'),
            HasMany::make('Conversations'),
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
        return [];
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
            new Filters\ContactStatus,
            new Filters\ContactByTag,
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
            new Lenses\ActiveContacts,
            new Lenses\RecentContacts,
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
            new Actions\ActivateContact,
            new Actions\DeactivateContact,
            new Actions\AddTag,
            new Actions\ExportContacts,
        ];
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Contacts';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'Contact';
    }

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return 'Contact Management';
    }

    /**
     * Determine if this resource is available for navigation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function availableForNavigation(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('manage-contacts');
    }

    /**
     * Determine if this resource can be viewed.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function viewable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('view-contacts');
    }

    /**
     * Determine if this resource can be created.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function creatable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('create-contacts');
    }

    /**
     * Determine if this resource can be updated.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function updatable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('edit-contacts');
    }

    /**
     * Determine if this resource can be deleted.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public static function deletable(NovaRequest $request)
    {
        return $request->user()->hasPermissionTo('delete-contacts');
    }
}
