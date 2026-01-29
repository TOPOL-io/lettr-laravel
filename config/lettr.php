<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lettr API Key
    |--------------------------------------------------------------------------
    |
    | Here you may specify your Lettr API key. This will be used to
    | authenticate with the Lettr API when sending emails.
    |
    */

    'api_key' => env('LETTR_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Project ID
    |--------------------------------------------------------------------------
    |
    | The default project ID to use when listing or fetching templates.
    | This can be overridden by passing an explicit project ID to the
    | template service methods.
    |
    */

    'default_project_id' => env('LETTR_DEFAULT_PROJECT_ID'),

];
