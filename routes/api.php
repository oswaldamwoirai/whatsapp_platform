<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// WhatsApp webhook endpoints
Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
    Route::get('/webhook', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify'])->name('webhook.verify');
    Route::post('/webhook', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])->name('webhook.handle');
});

// Protected API routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Contacts management
    Route::apiResource('contacts', App\Http\Controllers\Api\ContactController::class);
    Route::post('/contacts/import', [App\Http\Controllers\Api\ContactController::class, 'import'])->name('contacts.import');
    Route::post('/contacts/{contact}/tags', [App\Http\Controllers\Api\ContactController::class, 'addTags'])->name('contacts.tags.add');
    Route::delete('/contacts/{contact}/tags/{tag}', [App\Http\Controllers\Api\ContactController::class, 'removeTag'])->name('contacts.tags.remove');

    // Campaign management
    Route::apiResource('campaigns', App\Http\Controllers\Api\CampaignController::class);
    Route::post('/campaigns/{campaign}/send', [App\Http\Controllers\Api\CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/schedule', [App\Http\Controllers\Api\CampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('/campaigns/{campaign}/cancel', [App\Http\Controllers\Api\CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::get('/campaigns/{campaign}/analytics', [App\Http\Controllers\Api\CampaignController::class, 'analytics'])->name('campaigns.analytics');

    // Chatbot flows
    Route::apiResource('chatbot-flows', App\Http\Controllers\Api\ChatbotFlowController::class);
    Route::post('/chatbot-flows/{flow}/activate', [App\Http\Controllers\Api\ChatbotFlowController::class, 'activate'])->name('chatbot-flows.activate');
    Route::post('/chatbot-flows/{flow}/deactivate', [App\Http\Controllers\Api\ChatbotFlowController::class, 'deactivate'])->name('chatbot-flows.deactivate');
    Route::post('/chatbot-flows/{flow}/test', [App\Http\Controllers\Api\ChatbotFlowController::class, 'test'])->name('chatbot-flows.test');

    // Messages
    Route::apiResource('messages', App\Http\Controllers\Api\MessageController::class)->only(['index', 'show']);
    Route::post('/messages/send', [App\Http\Controllers\Api\MessageController::class, 'send'])->name('messages.send');
    Route::post('/messages/send-bulk', [App\Http\Controllers\Api\MessageController::class, 'sendBulk'])->name('messages.send.bulk');
    Route::post('/messages/{message}/resend', [App\Http\Controllers\Api\MessageController::class, 'resend'])->name('messages.resend');

    // Templates
    Route::apiResource('templates', App\Http\Controllers\Api\TemplateController::class);
    Route::post('/templates/sync', [App\Http\Controllers\Api\TemplateController::class, 'sync'])->name('templates.sync');
    Route::post('/templates/{template}/send', [App\Http\Controllers\Api\TemplateController::class, 'send'])->name('templates.send');

    // Media management
    Route::apiResource('media', App\Http\Controllers\Api\MediaController::class);
    Route::post('/media/upload', [App\Http\Controllers\Api\MediaController::class, 'upload'])->name('media.upload');

    // Conversations
    Route::apiResource('conversations', App\Http\Controllers\Api\ConversationController::class)->only(['index', 'show']);
    Route::post('/conversations/{conversation}/assign', [App\Http\Controllers\Api\ConversationController::class, 'assign'])->name('conversations.assign');
    Route::post('/conversations/{conversation}/resolve', [App\Http\Controllers\Api\ConversationController::class, 'resolve'])->name('conversations.resolve');
    Route::post('/conversations/{conversation}/messages', [App\Http\Controllers\Api\ConversationController::class, 'reply'])->name('conversations.reply');

    // Analytics and reporting
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/messages', [App\Http\Controllers\Api\AnalyticsController::class, 'messages'])->name('messages');
        Route::get('/contacts', [App\Http\Controllers\Api\AnalyticsController::class, 'contacts'])->name('contacts');
        Route::get('/campaigns', [App\Http\Controllers\Api\AnalyticsController::class, 'campaigns'])->name('campaigns');
        Route::get('/chatbot', [App\Http\Controllers\Api\AnalyticsController::class, 'chatbot'])->name('chatbot');
        Route::get('/export/{type}', [App\Http\Controllers\Api\AnalyticsController::class, 'export'])->name('export');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/whatsapp', [App\Http\Controllers\Api\SettingsController::class, 'whatsapp'])->name('whatsapp');
        Route::post('/whatsapp', [App\Http\Controllers\Api\SettingsController::class, 'updateWhatsApp'])->name('whatsapp.update');
        Route::get('/system', [App\Http\Controllers\Api\SettingsController::class, 'system'])->name('system');
        Route::post('/system', [App\Http\Controllers\Api\SettingsController::class, 'updateSystem'])->name('system.update');
    });
});
