<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Contact management
            'manage-contacts',
            'view-contacts',
            'create-contacts',
            'edit-contacts',
            'delete-contacts',
            'import-contacts',

            // Campaign management
            'manage-campaigns',
            'view-campaigns',
            'create-campaigns',
            'edit-campaigns',
            'delete-campaigns',
            'send-campaigns',

            // Chatbot flows
            'manage-chatbot-flows',
            'view-chatbot-flows',
            'create-chatbot-flows',
            'edit-chatbot-flows',
            'delete-chatbot-flows',
            'activate-chatbot-flows',

            // Templates
            'manage-templates',
            'view-templates',
            'create-templates',
            'edit-templates',
            'delete-templates',
            'sync-templates',

            // Media
            'manage-media',
            'view-media',
            'upload-media',
            'delete-media',

            // Conversations
            'manage-conversations',
            'view-conversations',
            'assign-conversations',
            'resolve-conversations',
            'reply-conversations',

            // Analytics
            'view-analytics',
            'export-analytics',

            // Settings
            'manage-settings',
            'view-settings',
            'edit-settings',

            // WhatsApp integration
            'manage-integrations',
            'view-integrations',
            'edit-integrations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'view-users',
            'manage-contacts',
            'manage-campaigns',
            'manage-chatbot-flows',
            'manage-templates',
            'manage-media',
            'manage-conversations',
            'view-analytics',
            'view-settings',
            'view-integrations',
        ]);

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->givePermissionTo([
            'view-contacts',
            'create-contacts',
            'edit-contacts',
            'view-campaigns',
            'view-chatbot-flows',
            'view-templates',
            'view-media',
            'manage-conversations',
            'view-analytics',
        ]);

        // Create default super admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@whatsapp-platform.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'phone' => '+1234567890',
                'status' => 'active',
            ]
        );

        $user->assignRole('super-admin');
    }
}
