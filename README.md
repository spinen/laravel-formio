# SPINEN's Laravel Formio

[![Latest Stable Version](https://poser.pugx.org/spinen/laravel-formio/v/stable)](https://packagist.org/packages/spinen/laravel-formio)
[![Latest Unstable Version](https://poser.pugx.org/spinen/laravel-formio/v/unstable)](https://packagist.org/packages/spinen/laravel-formio)
[![Total Downloads](https://poser.pugx.org/spinen/laravel-formio/downloads)](https://packagist.org/packages/spinen/laravel-formio)
[![License](https://poser.pugx.org/spinen/laravel-formio/license)](https://packagist.org/packages/spinen/laravel-formio)

PHP package to interface with [Formio](https://www.form.io)

We solely use [Laravel](https://www.laravel.com) for our applications, so this package is written with Laravel in mind. We have tried to make it work outside of Laravel. If there is a request from the community to split this package into 2 parts, then we will consider doing that work.

## Build Status

| Branch | Status | Coverage | Code Quality |
| ------ | :----: | :------: | :----------: |
| Develop | [![Build Status](https://travis-ci.org/spinen/laravel-formio.svg?branch=develop)](https://github.com/spinen/laravel-formio/workflows/CI/badge.svg?branch=develop) | [![Code Coverage](https://scrutinizer-ci.com/g/spinen/laravel-formio/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-formio/?branch=develop) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-formio/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-formio/?branch=develop) |
| Master | [![Build Status](https://github.com/spinen/laravel-formio/workflows/CI/badge.svg?branch=master)](https://github.com/spinen/laravel-formio/workflows/CI/badge.svg?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/spinen/laravel-formio/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/spinen/laravel-formio/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-formio/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/spinen/laravel-formio/?branch=master) |

## Installation

Install Formio PHP Package:

```bash
$ composer require spinen/laravel-formio
```

The package uses the [auto registration feature](https://laravel.com/docs/master/packages#package-discovery) of Laravel.

## Usage

The primary class is `Spinen\Formio\Client`.  It gets constructed with 3 parameters...

* `array $config` - Configuration properties.  See the `formio.php` file in the `./config` directory for a documented list of options.

* `Guzzle $guzzle` - Instance of `GuzzleHttp\Client`

* `Token $token` - _[Optional]_ Instance of `Spinen\Formio\Token`

Once you new up a `Client` instance, you have the following methods...

* `addUser(FormioUser $user, $password = null)` - Add the user to the `user` resource in Formio.  If no password is provided, then if a default password is specified in the config, it is used. Otherwise, generate a random 32 character string.  Once the user is added to Formio, then the password is set on the `formio_password` property of the `$user` object, and the `save` method is called on it to persist the password for future interactions with Formio for the user. Finally, set the user's JWT on the `$token`, so that requests are made via the user.

* `login(FormioUser $user = null)` - If a `$user` is provided, then log the user into Formio with the array provided by the `getLoginData` method on the `$user` object.  Otherwise, log in the admin user from the config.

* `logout()` - Null the `$token`

* `request($path, $data = [], $method = 'GET')` - Make an [API](https://help.form.io/developer/api/) call to Formio to `$path` with the `$data` using the JWT for the logged in user.

* `setConfigs(array $configs)` - Allow overriding the `$configs` on the `Client` instance.

* `sso(FormioUser $user)` - If the `$user` instance has a value set for `formio_password` property, then get a JWT from Formio via API.  Otherwise, generate a [Custom JWT](https://help.form.io/integrations/sso/) for the user.

* `uri($path = null)` - Generate a full uri for the path on the Formio server.


## Laravel

### Configuration

1. You will need to make your `User` object implement `Spinen\Formio\Contracts\FormioUser` so that it will have the required methods.  We have also included the `Spinen\Formio\Concerns\HasForms` trait which will satisfy the contract.

    ```php
    <?php

    namespace App;

    use Illuminate\Contracts\Auth\MustVerifyEmail;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;
    use Spinen\Formio\Concerns\HasForms;
    use Spinen\Formio\Contracts\FormioUser;

    class User extends Authenticatable implements FormioUser
    {
        use FormioUser, Notifiable;

        // ...
    }
    ```

2. Add the appropriate values to your ```.env```

    #### Minimal Keys

    ```bash
    FORMIO_ADMIN_PASSWORD=<admin password in formio>
    FORMIO_ADMIN_USERNAME=<admin username in formio>
    FORMIO_URL=<url to formio>
    ```

    #### Optional Keys
    ```bash
    FORMIO_JWT_ALGORITHM=<jwt algorithm>                   # Default: HS256
    FORMIO_JWT_SECRET=<jwt secret>                         # Default: --- change me now --- (same as docker image)
    FORMIO_PROJECT_ID=<project id>                         # Default: null
    FORMIO_USER_FORM=<id of the user resource>             # Default: null (Will lookup the id for "user" if null)
    FORMIO_USER_ROLES=<comma seperated list of user roles> # Default: null (Will lookup Authenticated if null)
    ```

3. _[Optional]_ Publish config & migration

    #### Config
    A configuration file named ```formio.php``` can be published to ```config/``` by running...

    ```bash
    php artisan vendor:publish --tag=formio-config
    ```

    #### Migration
    Migrations files can be published by running...

    ```bash
    php artisan vendor:publish --tag=formio-migrations
    ```

### JS API

By default, there is a route published at `/api/formio/jwt` which is behind the `api` & `auth:api` middlewares, so from your js code, you can request a JWT for the authenticated user from the route...

```js
axios.get('/api/formio/jwt')
    .then(({ data: { data: { expires_at, jwt } } }) => {
        this.jwt = jwt;
        this.jwt_expires_at = expires_at;

        // Do something with the form like Formio.setToken(jwt)
    })
    .catch((e) => {
        this.errors.push(e);
    });
```

to get a payload like this...

```json
{
    "data": {
        "expires_at": "2019-10-04T20:29:58+00:00",
        "jwt": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHRlcm5hbCI6dHJ1ZSwiZm9ybSI6eyJfaWQiOiI1ZDkxNmIyNjBlYTBjNzAwMWE4ZWVlM2MifSwidXNlciI6eyJfaWQiOiJleHRlcm5hbCIsImRhdGEiOnsiZW1haWwiOiJvamFjb2JzQGV4YW1wbGUub3JnIiwiZmlyc3ROYW1lIjoiQXJ2aWQiLCJsYXN0TmFtZSI6IlJ1ZWNrZXIifSwicm9sZXMiOlsiNWQ5MTZiMjYwZWEwYzcwMDFhOGVlZTNhIl19LCJpYXQiOjE1NzAxMzg3MjIsImV4cCI6MTU3MDE1MzEyMn0.ZxWTJIteHXomGz1F7yYjSJcXvWLZQZYRrPN4cKB3KAk"
    }
}
```

### Examples

Here is an example of getting the roles as admin:

```php
$ php artisan tinker
Psy Shell v0.9.9 (PHP 7.2.14 — cli) by Justin Hileman
>>> $formio = app(Spinen\Formio\Client::class)
=> Spinen\Formio\Client {#3534
     +token: Spinen\Formio\Token {#3528
       // ...
     },
   }
>>> $formio->login()
=> Spinen\Formio\Client {#3534
     +token: Spinen\Formio\Token {#3528
       // ...
       +user: [
         "_id" => "5d916b270ea0c7001a8eee4a",
         "owner" => null,
         "roles" => [
           "5d916b260ea0c7001a8eee39",
         ],
         "form" => "5d916b260ea0c7001a8eee3d",
         "data" => [
           "email" => "admin@domain.com",
         ],
         // ...
       ],
     },
   }
>>> $formio->request('/role')
=> [
     [
       "_id" => "5d916b260ea0c7001a8eee39",
       "description" => "A role for Administrative Users.",
       "default" => false,
       "admin" => true,
       "title" => "Administrator",
       "machineName" => "administrator",
       "created" => "2019-09-30T02:40:38.377Z",
       "modified" => "2019-09-30T02:40:38.491Z",
     ],
     [
       "_id" => "5d916b260ea0c7001a8eee3b",
       "description" => "A role for Anonymous Users.",
       "default" => true,
       "admin" => false,
       "title" => "Anonymous",
       "machineName" => "anonymous",
       "created" => "2019-09-30T02:40:38.735Z",
       "modified" => "2019-09-30T02:40:38.737Z",
     ],
     [
       "_id" => "5d916b260ea0c7001a8eee3a",
       "description" => "A role for Authenticated Users.",
       "default" => false,
       "admin" => false,
       "title" => "Authenticated",
       "machineName" => "authenticated",
       "created" => "2019-09-30T02:40:38.648Z",
       "modified" => "2019-09-30T02:40:38.682Z",
     ],
   ]
>>>
```

Here is an example of getting the form names as a user with a custom JWT:

```php
$ php artisan tinker
>>> $user = factory(App\User::class)->create()
=> App\User {#3565
     first_name: "Arvid",
     last_name: "Ruecker",
     email: "ojacobs@example.org",
     email_verified_at: "2019-10-03 21:38:33",
     updated_at: "2019-10-03 21:38:33",
     created_at: "2019-10-03 21:38:33",
     id: 1,
   }
>>> $formio = app(Spinen\Formio\Client::class)
=> Spinen\Formio\Client {#3697
     +token: Spinen\Formio\Token {#3530
       // ...
     },
   }
>>> $formio->sso($user)
=> Spinen\Formio\Client {#3697
     +token: Spinen\Formio\Token {#3530
       +expires_at: Carbon\Carbon @1570153122 {#3695
         date: 2019-10-04 01:38:42.0 +00:00,
       },
       +issued_at: Carbon\Carbon @1570138722 {#3619
         date: 2019-10-03 21:38:42.0 +00:00,
       },
       +jwt: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHRlcm5hbCI6dHJ1ZSwiZm9ybSI6eyJfaWQiOiI1ZDkxNmIyNjBlYTBjNzAwMWE4ZWVlM2MifSwidXNlciI6eyJfaWQiOiJleHRlcm5hbCIsImRhdGEiOnsiZW1haWwiOiJvamFjb2JzQGV4YW1wbGUub3JnIiwiZmlyc3ROYW1lIjoiQXJ2aWQiLCJsYXN0TmFtZSI6IlJ1ZWNrZXIifSwicm9sZXMiOlsiNWQ5MTZiMjYwZWEwYzcwMDFhOGVlZTNhIl19LCJpYXQiOjE1NzAxMzg3MjIsImV4cCI6MTU3MDE1MzEyMn0.ZxWTJIteHXomGz1F7yYjSJcXvWLZQZYRrPN4cKB3KAk",
       +jwt_obj: {#3575
         +"external": true,
         +"form": {#3535
           +"_id": "5d916b260ea0c7001a8eee3c",
         },
         +"user": {#3676
           +"_id": "external",
           +"data": {#3527
             +"email": "ojacobs@example.org",
             +"firstName": "Arvid",
             +"lastName": "Ruecker",
           },
           +"roles": [
             "5d916b260ea0c7001a8eee3a",
           ],
         },
         +"iat": 1570138722,
         +"exp": 1570153122,
       },
       +user: [
         "email" => "ojacobs@example.org",
         "firstName" => "Arvid",
         "lastName" => "Ruecker",
       ],
     },
   }
>>> collect($formio->request('/form'))->pluck('name')
=> Illuminate\Support\Collection {#3536
     all: [
       "user",
       "admin",
       "userLogin",
       "adminLogin",
       "userRegister",
     ],
   }
>>>
```

## Generic PHP

### Examples

Here is an example of getting the current user as admin:

```php
$ psysh
Psy Shell v0.9.9 (PHP 7.2.22 — cli) by Justin Hileman
>>> $config = [
     "admin" => [
       "password" => "password",
       "login" => [
         "path" => "/admin/login",
       ],
       "username" => "admin@domain.com",
     ],
     "jwt" => [
       "algorithm" => "HS256",
       "secret" => "--- change me now ---",
     ],
     "project" => [
       "id" => null,
     ],
     "url" => "http://localhost:3001",
     "user" => [
       "form" => null,
       "login" => [
         "path" => "/user/login",
       ],
       "register" => [
         "default_password" => null,
         "path" => "/user/register",
       ],
       "roles" => [],
       "sync" => false,
     ],
   ]
>>> $guzzle = new GuzzleHttp\Client();
=> GuzzleHttp\Client {#2346}
>>> $formio = new Spinen\Formio\Client($config, $guzzle);
=> Spinen\Formio\Client {#2364
     +token: Spinen\Formio\Token {#2362
       // ...
     },
   }
>>> $formio->login();
=> Spinen\Formio\Client {#2364
     +token: Spinen\Formio\Token {#2362
       // ...
       +user: [
         "_id" => "5d916b270ea0c7001a8eee4a",
         "owner" => null,
         "roles" => [
           "5d916b260ea0c7001a8eee39",
         ],
         "form" => "5d916b260ea0c7001a8eee3d",
         "data" => [
           "email" => "admin@domain.com",
         ],
         "access" => [],
         "externalIds" => [],
         "created" => "2019-09-30T02:40:39.478Z",
         "modified" => "2019-09-30T02:40:39.482Z",
       ],
     },
   }
>>> $formio->request('/current')
=> [
     "_id" => "5d916b270ea0c7001a8eee4a",
     "owner" => null,
     "roles" => [
       "5d916b260ea0c7001a8eee39",
     ],
     "form" => "5d916b260ea0c7001a8eee3d",
     "data" => [
       "email" => "admin@domain.com",
     ],
     "access" => [],
     "externalIds" => [],
     "created" => "2019-09-30T02:40:39.478Z",
     "modified" => "2019-09-30T02:40:39.482Z",
   ]
>>>
```
