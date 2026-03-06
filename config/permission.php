<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permissions Default Guard
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" that will be used
    | by the package. You may change this value if you want to use a different
    | authentication guard for the internal permission checks.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Permissions Http Only
    |--------------------------------------------------------------------------
    |
    | This option determines if the cookies should be marked as HTTP only. If
    | this is set to true, JavaScript will not be able to access the cookie.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Permissions Same Site
    |--------------------------------------------------------------------------
    |
    | This option determines if the cookies should have the "SameSite" attribute
    | set. If this is set to true, the cookie will only be sent over HTTPS.
    |
    */

    'same_site' => 'lax',

    /*
    |--------------------------------------------------------------------------
    | Permissions Register Permission Checkpoints
    |--------------------------------------------------------------------------
    |
    | This option determines if the package will register permission checkpoints.
    | If this is set to true, the package will register permission checkpoints.
    |
    */

    'register_permission_checkpoints' => false,

    /*
    |--------------------------------------------------------------------------
    | Permissions Teams
    |--------------------------------------------------------------------------
    |
    | This option determines if the package will use teams. If this is set to
    | true, the package will use teams.
    |
    */

    'teams' => false,

    /*
    |--------------------------------------------------------------------------
    | Permissions Cache
    |--------------------------------------------------------------------------
    |
    | This option determines if the package will cache permissions. If this is
    | set to true, the package will cache permissions.
    |
    */

    'cache' => [
        'expiration_time' => 86400, // 24 hours
        'key' => 'spatie.permission.cache',
        'store' => 'default', // default cache store
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Models
    |--------------------------------------------------------------------------
    |
    | This option determines which models will be used for the package.
    |
    */

    'models' => [
        /*
        |--------------------------------------------------------------------------
        | Permission Model
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | Eloquent model should be used to retrieve your permissions. Of course, the
        | default model is included in the package, but you may change it if you
        | want.
        |
        */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
        |--------------------------------------------------------------------------
        | Role Model
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | Eloquent model should be used to retrieve your roles. Of course, the
        | default model is included in the package, but you may change it if you
        | want.
        |
        */

        'role' => Spatie\Permission\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Table Names
    |--------------------------------------------------------------------------
    |
    | Here you may set the table names used by the package to store the
    | permissions and roles in the database.
    |
    */

    'table_names' => [
        'permissions' => 'permissions',
        'roles' => 'roles',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Column Names
    |--------------------------------------------------------------------------
    |
    | Here you may set the column names used in the migrations for the
    | permissions and roles tables.
    |
    */

    'column_names' => [
        /*
        |--------------------------------------------------------------------------
        | Team Foreign Key
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | column should be used to retrieve the model's team foreign key. The
        | default value is "team_id", but you may change it if you want.
        |
        */

        'team_foreign_key' => 'team_id',

        /*
        |--------------------------------------------------------------------------
        | Model Morph Key
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | column should be used to retrieve the model's morph key. The default
        | value is "model_id", but you may change it if you want.
        |
        */

        'model_morph_key' => 'model_id',

        /*
        |--------------------------------------------------------------------------
        | Permissions Pivot Foreign Key
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | column should be used to retrieve the permission's pivot foreign key.
        | The default value is "permission_id", but you may change it if you want.
        |
        */

        'permission_pivot_key' => 'permission_id',

        /*
        |--------------------------------------------------------------------------
        | Roles Pivot Foreign Key
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | column should be used to retrieve the role's pivot foreign key. The
        | default value is "role_id", but you may change it if you want.
        |
        */

        'role_pivot_key' => 'role_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Super Admin
    |--------------------------------------------------------------------------
    |
    | This option determines if the package will use a super admin. If this is
    | set to true, the package will use a super admin.
    |
    */

    'super_admin' => [
        'name' => 'Super Admin',
        'permissions' => ['*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Middleware
    |--------------------------------------------------------------------------
    |
    | This option determines if the package will register middleware. If this is
    | set to true, the package will register middleware.
    |
    */

    'middleware' => [
        'permission' => Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role' => Spatie\Permission\Middlewares\RoleMiddleware::class,
    ],

];
