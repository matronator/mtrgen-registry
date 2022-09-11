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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->group(['prefix' => 'templates'], function () use ($router) {
        $router->get('/', ['uses' => 'TemplateController@findAll']);

        $router->get('{vendor}', ['uses' => 'TemplateController@findByVendor']);

        $router->get('{vendor}/{name}', ['uses' => 'TemplateController@findByName']);

        $router->get('{vendor}/{name}/get', ['uses' => 'TemplateController@get']);

        $router->post('/', ['middleware' => 'verify', 'uses' => 'TemplateController@save']);
    });

    $router->post('signup', ['uses' => 'UserController@create']);
});
