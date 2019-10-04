<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::get(Config::get('formio.route.uri', 'api/formio/jwt'), 'FormioController@jwt')
     ->name(Config::get('formio.route.name', 'formio.jwt'));
