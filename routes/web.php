<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () {
    return redirect('https://matronator.github.io/MTRGen/', '301');
});

$router->group(['middleware' => 'cors', 'prefix' => 'api'], function () use ($router) {
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });
    $router->group(['prefix' => 'templates'], function () use ($router) {
        $router->get('/', ['uses' => 'TemplateController@findAll']);

        $router->get('{vendor}', ['uses' => 'TemplateController@findByVendor']);
    
        $router->get('{vendor}/{name}', ['uses' => 'TemplateController@findByName']);

        $router->get('{vendor}/{name}/details', ['uses' => 'TemplateController@getTemplateDetails']);
    
        $router->get('{vendor}/{name}/get', ['uses' => 'TemplateController@get']);

        $router->get('{vendor}/{name}/type', ['uses' => 'TemplateController@getType']);
    
        $router->post('/', ['middleware' => 'auth', 'uses' => 'TemplateController@save']);

        $router->post('/publish', ['middleware' => 'auth', 'uses' => 'TemplateController@publish']);
    });

    $router->group(['prefix' => 'bundles'], function () use ($router) {
        $router->post('/', ['middleware' => 'auth', 'uses' => 'TemplateController@saveBundle']);

        $router->get('{vendor}/{name}/{templateName}/get', ['uses' => 'TemplateController@getFromBundle']);

        $router->get('/', function() use ($router) {
            return $router->app->version();
        });
    });

    $router->group(['prefix' => 'users'], function () use ($router) {
        $router->get('/', ['uses' => 'UserController@findAll']);
        $router->get('{username}', ['uses' => 'UserController@findByName']);

        $router->put('/', ['middleware' => 'auth', 'uses' => 'UserController@update']);

        $router->get('{username}/avatar', ['uses' => 'UserController@getAvatar']);
        $router->post('/avatar', ['middleware' => 'auth', 'uses' => 'UserController@setAvatar']);
    });

    $router->group(['middleware' => 'auth', 'prefix' => 'dashboard'], function () use ($router) {
        $router->post('/', ['uses' => 'UserController@getLoggedUser']);
        $router->post('/check', ['uses' => 'UserController@isUserLoggedIn']);
    });
    
    $router->post('signup', ['uses' => 'UserController@create']);
    $router->post('login', ['middleware' => 'login', 'uses' => 'UserController@login']);
    $router->post('logout', ['middleware' => 'auth', 'uses' => 'UserController@logout']);
});
