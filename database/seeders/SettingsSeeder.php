<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // WhatsApp Settings
        Setting::set('whatsapp_access_token', '', 'text');
        Setting::set('whatsapp_phone_number_id', '', 'text');
        Setting::set('whatsapp_webhook_verify_token', '', 'text');
        Setting::set('whatsapp_api_version', 'v18.0', 'text');

        // System Settings
        Setting::set('app_name', 'WhatsApp Automation Platform', 'text');
        Setting::set('max_file_size', 10240, 'integer');
        Setting::set('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,mp3', 'text');
        Setting::set('rate_limit_per_minute', 60, 'integer');
        Setting::set('bulk_message_rate_limit', 1000, 'integer');

        // Notification Settings
        Setting::set('enable_email_notifications', true, 'boolean');
        Setting::set('enable_push_notifications', false, 'boolean');

        // Auto Response Settings
        Setting::set('enable_auto_response', false, 'boolean');
        Setting::set('auto_response_message', 'Thank you for your message. We will get back to you soon.', 'text');
        Setting::set('auto_response_delay', 5, 'integer');

        // Business Hours
        Setting::set('business_hours_enabled', false, 'boolean');
        Setting::set('business_hours_start', '09:00', 'text');
        Setting::set('business_hours_end', '18:00', 'text');
        Setting::set('business_hours_timezone', 'UTC', 'text');
    }
}
