<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Class Namespace
    |---------------------------------------------------------------------------
    |
    | This value sets the root class namespace for Livewire component classes in
    | your application. This value will change where component auto-discovery
    | finds components. It's also referenced by the file creation commands.
    |
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |---------------------------------------------------------------------------
    | Livewire Asset URL
    |---------------------------------------------------------------------------
    |
    | This value sets the path to Livewire JavaScript assets, for cases where
    | your app's domain root is not the correct path. By default, Livewire
    | will load its JavaScript assets from the app's "relative root".
    |
    */

    'asset_url' => env('APP_URL', 'https://orsozox.com/forums') . '/vendor/livewire/livewire.js',

    /*
    |---------------------------------------------------------------------------
    | View Path
    |---------------------------------------------------------------------------
    |
    | This value is the path where Livewire will look for component views. It
    | will also be used as the default path when creating robust components
    | with the `livewire:make` command.
    |
    */

    'view_path' => resource_path('views/livewire'),

    /*
    |---------------------------------------------------------------------------
    | Layout
    |---------------------------------------------------------------------------
    |
    | The view that will be used as the layout when rendering a single component
    | as an entire page.
    |
    */

    'layout' => 'components.layouts.app',

    /*
    |---------------------------------------------------------------------------
    | Lazy Loading
    |---------------------------------------------------------------------------
    |
    | Here you can specify a default placeholder view that will be used when
    | a component is "lazy" loaded.
    |
    */

    'lazy_placeholder' => null,

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing uploads in a temporary directory
    | before the developer saves them. Here you can configure the temporary
    | directory and where they are stored.
    |
    */

    'temporary_file_upload' => [
        'disk' => null,        // Example: 'local', 's3'              | Default: 'default'
        'rules' => null,       // Example: ['file', 'mimes:png,jpg']  | Default: ['required', 'file', 'max:12288'] (12MB)
        'directory' => null,   // Example: 'tmp'                      | Default: 'livewire-tmp'
        'middleware' => null,  // Example: 'throttle:5,1'             | Default: 'throttle:59,1'
        'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs...
            'png',
            'gif',
            'bmp',
            'svg',
            'wav',
            'mp4',
            'mov',
            'avi',
            'wmv',
            'mp3',
            'm4a',
            'jpg',
            'jpeg',
            'mpga',
            'webp',
            'wma',
        ],
        'max_upload_time' => 5, // Max duration (in minutes) before an upload gets invalidated...
    ],

    /*
    |---------------------------------------------------------------------------
    | Render On Redirect
    |---------------------------------------------------------------------------
    |
    | This value determines if Livewire will run a component's `render()` method
    | after a redirect has been triggered using something like `redirect(...)`.
    | Setting this to true will result in a performance decrease on redirects.
    |
    */

    'render_on_redirect' => false,

    /*
    |---------------------------------------------------------------------------
    | Eloquent Model Binding
    |---------------------------------------------------------------------------
    |
    | This value determines if Livewire components can be passed Eloquent models
    | and have them automatically serialized and deserialized.
    |
    */

    'legacy_model_binding' => false,

    /*
    |---------------------------------------------------------------------------
    | Inject Assets
    |---------------------------------------------------------------------------
    |
    | By default, Livewire injects its JavaScript and CSS into the <head> and
    | <body> of pages containing Livewire components. By disabling this you
    | have to manually include `@livewireStyles` and `@livewireScripts`.
    |
    */

    'inject_assets' => true,

    /*
    |---------------------------------------------------------------------------
    | Navigate
    |---------------------------------------------------------------------------
    |
    | By adding `wire:navigate` to links in your Livewire application, Livewire
    | will prevent the default link handling and instead request those pages
    | via AJAX, creating an SPA-like feel. Here you can configure navigate.
    |
    */

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    /*
    |---------------------------------------------------------------------------
    | Inject Morph Markers
    |---------------------------------------------------------------------------
    |
    | Livewire utilizes DOM morphing for updating the browser's DOM. Because
    | that's such a complex operation, Livewire outputs HTML comments like
    | <!--[if BLOCK]><![endif]--> to help keep track of things. You can
    | disable this behavior here if you need to.
    |
    */

    'inject_morph_markers' => true,

    /*
    |---------------------------------------------------------------------------
    | Pagination Theme
    |---------------------------------------------------------------------------
    |
    | When enabling pagination within Livewire, it replaces the tailwind views
    | of Laravel with its own views. Here you can dictate which views to use.
    | Supported: 'tailwind', 'bootstrap'.
    |
    */

    'pagination_theme' => 'tailwind',

];
