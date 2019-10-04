<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrator
    |--------------------------------------------------------------------------
    |
    | Admin user to work with Formio.  If no user is being used, then this is
    | the account to make API calls.
    |
    */
    'admin'   => [

        /*
        |--------------------------------------------------------------------------
        | Admin password
        |--------------------------------------------------------------------------
        */
        'password' => env('FORMIO_ADMIN_PASSWORD'),

        /*
        |--------------------------------------------------------------------------
        | Admin login
        |--------------------------------------------------------------------------
        |
        | Admin login.
        |
        */
        'login'    => [

            /*
            |--------------------------------------------------------------------------
            | Path
            |--------------------------------------------------------------------------
            |
            | Path to the admin login
            |
            */
            'path' => '/admin/login',

        ],

        /*
        |--------------------------------------------------------------------------
        | Admin username
        |--------------------------------------------------------------------------
        */
        'username' => env('FORMIO_ADMIN_USERNAME'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Javascript Web Token (JWT)
    |--------------------------------------------------------------------------
    |
    | Configure the JWT token
    |
    */
    'jwt'     => [

        /*
        |--------------------------------------------------------------------------
        | Algorithm used to encrypt the token
        |--------------------------------------------------------------------------
        |
        | Must match the Formio server
        |
        */
        'algorithm' => env('FORMIO_JWT_ALGORITHM', 'HS256'),

        /*
        |--------------------------------------------------------------------------
        | Secret used to encrypt the token
        |--------------------------------------------------------------------------
        |
        | Must match the Formio server
        |
        */
        'secret'    => env('FORMIO_JWT_SECRET', '--- change me now ---'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Project
    |--------------------------------------------------------------------------
    |
    | When using the enterprise Formio, then there can be multiple projects
    | for the account.
    |
    */
    'project' => [

        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        |
        | Id of the project being used.  If `null`, then assume using OpenSource
        | Formio, which does not need a project.
        |
        */
        'id' => env('FORMIO_PROJECT_ID', null),

    ],

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    |
    | A route to show the Formio JWT
    |
    */
    'route'   => [

        // Expose jwt route?
        'enabled'    => true,

        // Middleware to use on the route
        'middleware' => ['api', 'auth:api'],

        // Name of route
        'name'       => 'api.formio.jwt',

        // URI to reach the jwt
        'uri'        => '/api/formio/jwt',

    ],

    /*
    |--------------------------------------------------------------------------
    | Formio URL
    |--------------------------------------------------------------------------
    |
    | The URL to the Formio server
    |
    */
    'url'     => env('FORMIO_URL', 'http://localhost:3001'),

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    |
    | Regular user configuration.
    |
    */
    'user'    => [

        /*
        |--------------------------------------------------------------------------
        | Form
        |--------------------------------------------------------------------------
        |
        | This is the ID of the form/resource that would contains the user records.
        |
        | NOTE: For Laravel, if null, then we automatically get the "user" _id
        |
        */
        'form'     => env('FORMIO_USER_FORM', null),

        /*
        |--------------------------------------------------------------------------
        | User login
        |--------------------------------------------------------------------------
        |
        | Regular user login.
        |
        */
        'login'    => [

            /*
            |--------------------------------------------------------------------------
            | Path
            |--------------------------------------------------------------------------
            |
            | Path to the user login
            |
            */
            'path' => '/user/login',

        ],

        /*
        |--------------------------------------------------------------------------
        | User login
        |--------------------------------------------------------------------------
        |
        | Regular user login.
        |
        */
        'register' => [

            /*
            |--------------------------------------------------------------------------
            | Default user password
            |--------------------------------------------------------------------------
            |
            | If no password is provided when adding or syncing a user, this key can be
            | set to be used as the password so that all Formio users would have the
            | same password If no password is set, then it will generate a random 32
            | character string.
            |
            */
            'default_password' => null,

            /*
            |--------------------------------------------------------------------------
            | Registration path
            |--------------------------------------------------------------------------
            |
            | Path to the user registration
            |
            */
            'path'             => '/user/register',

        ],

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        |
        | Comma seperated list of roles that the user has.
        |
        | NOTE: For Laravel, if null, then we automatically get the "Authenticated"
        |
        */
        'roles' => array_filter(explode(',', env('FORMIO_USER_ROLES'))),

        /*
        |--------------------------------------------------------------------------
        | Sync users
        |--------------------------------------------------------------------------
        |
        | Should registered users be synced to Formio?
        |
        */
        'sync'  => false,

    ],

];
