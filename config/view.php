<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | which storage engine Laravel should use for your views. Of course
    | you're free to use multiple storage engines at the same time.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be stored
    | for your application. Typically, this is within the storage directory.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

    /*
    |--------------------------------------------------------------------------
    | View Engines
    |--------------------------------------------------------------------------
    |
    | Laravel's view engine resolution. You may add additional engines to the
    | array if you want to use template engines other than Blade.
    |
    */

    'engines' => [
        'blade' => [
            'class' => Illuminate\View\Engines\CompilerEngine::class,
            'compiler' => Illuminate\View\Compilers\BladeCompiler::class,
        ],
    ],

];
