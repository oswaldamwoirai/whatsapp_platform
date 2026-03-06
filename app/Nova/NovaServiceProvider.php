<?php

namespace App\Nova;

use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use App\Nova\UserResource;
use App\Nova\ContactResource;
use App\Nova\CampaignResource;
use App\Nova\MessageResource;
use App\Nova\ConversationResource;
use App\Nova\ChatbotFlowResource;
use App\Nova\TemplateResource;
use App\Nova\MediaFileResource;
use App\Nova\TagResource;
use App\Nova\SettingResource;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Customize Nova
        Nova::mainMenu(function ($request) {
            return [
                MenuSection::make('Dashboard')
                    ->path('/dashboard')
                    ->icon('chart-bar'),

                MenuSection::resources('User Management', [
                    UserResource::class,
                ])->icon('users')->collapsible(),

                MenuSection::resources('Contact Management', [
                    ContactResource::class,
                    TagResource::class,
                ])->icon('user-group')->collapsible(),

                MenuSection::resources('Campaign Management', [
                    CampaignResource::class,
                    MessageResource::class,
                ])->icon('megaphone')->collapsible(),

                MenuSection::resources('Chatbot & Automation', [
                    ChatbotFlowResource::class,
                    TemplateResource::class,
                ])->icon('cog')->collapsible(),

                MenuSection::resources('Media & Files', [
                    MediaFileResource::class,
                ])->icon('photograph')->collapsible(),

                MenuSection::resources('Conversations', [
                    ConversationResource::class,
                ])->icon('chat')->collapsible(),

                MenuSection::resources('Settings', [
                    SettingResource::class,
                ])->icon('cog')->collapsible(),
            ];
        });
    }

    /**
     * Register the Nova routes.
     */
    protected function routes(): void
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                'admin@whatsapp-platform.com',
            ]);
        });
    }

    /**
     * Get the cards that should be displayed on the Nova dashboard.
     */
    protected function dashboards(): array
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the resources that should be listed in the Nova sidebar.
     */
    public static function resources(): array
    {
        return [
            UserResource::class,
            ContactResource::class,
            CampaignResource::class,
            MessageResource::class,
            ConversationResource::class,
            ChatbotFlowResource::class,
            TemplateResource::class,
            MediaFileResource::class,
            TagResource::class,
            SettingResource::class,
        ];
    }
}
