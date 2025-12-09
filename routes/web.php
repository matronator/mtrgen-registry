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


$router->group(['middleware' => 'cors', 'prefix' => 'api'], function () use ($router) {
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });
    $router->options('{any:.*}', ['uses' => 'TemplateController@options']);

    $router->group(['prefix' => 'generate'], function () use ($router) {
        $router->post('/', ['uses' => 'GeneratorController@generate']);

        $router->group(['prefix' => 'contract'], function () use ($router) {
            //@: api/generate/contract/token
            $router->post('/token', ['middleware' => 'deploy', 'uses' => 'GeneratorController@clarityTokenContract']);
        });
    });

    $router->group(['prefix' => 'templates'], function () use ($router) {
        $router->get('/', ['uses' => 'TemplateController@findAllPublic']);

        $router->get('{vendor}', ['uses' => 'TemplateController@findPublicByVendor']);

        $router->get('{vendor}/{name}', ['uses' => 'TemplateController@findPublicByName']);

        $router->get('{vendor}/{name}/details', ['uses' => 'TemplateController@getPublicTemplateDetails']);

        $router->get('{vendor}/{name}/get', ['uses' => 'TemplateController@getPublic']);

        $router->get('{vendor}/{name}/type', ['uses' => 'TemplateController@getType']);

        $router->post('/', ['middleware' => 'auth', 'uses' => 'TemplateController@save']);

        $router->post('/publish', ['middleware' => 'auth', 'uses' => 'TemplateController@publish']);

        $router->post('/convert', ['uses' => 'TemplateController@convert']);
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

        $router->post('/', ['middleware' => 'auth', 'uses' => 'UserController@update']);

        $router->get('{username}/avatar', ['uses' => 'UserController@getAvatar']);
        $router->post('/avatar', ['middleware' => 'auth', 'uses' => 'UserController@setAvatar']);
    });

    $router->group(['middleware' => 'auth', 'prefix' => 'dashboard'], function () use ($router) {
        $router->post('/', ['uses' => 'UserController@getLoggedUser']);
        $router->post('/check', ['uses' => 'UserController@isUserLoggedIn']);

        $router->group(['prefix' => 'templates'], function () use ($router) {
            $router->post('{vendor}', ['uses' => 'TemplateController@findByVendor']);

            $router->post('{vendor}/{name}', ['uses' => 'TemplateController@findByName']);

            $router->post('{vendor}/{name}/details', ['uses' => 'TemplateController@getTemplateDetails']);

            $router->post('{vendor}/{name}/get', ['uses' => 'TemplateController@get']);

            $router->get('{vendor}/{name}/type', ['uses' => 'TemplateController@getType']);

            $router->post('{vendor}/{name}/visibility', ['uses' => 'TemplateController@setVisibility']);
        });
    });

    $router->post('signup', ['uses' => 'UserController@create']);
    $router->post('login', ['middleware' => 'login', 'uses' => 'UserController@login']);
    $router->post('logout', ['middleware' => 'auth', 'uses' => 'UserController@logout']);
    $router->post('connect', ['middleware' => 'stacks', 'uses' => 'UserController@connectWallet']);
    $router->post('deploy', ['middleware' => ['stacks', 'deploy'], 'uses' => 'UserController@deployContract']);
    $router->get('deposit-address', ['uses' => 'UserController@getDepositAddress']);
});

$router->get('/{any:.*}', function () use ($router) {
    return view('index');
    // return $router->app->version();
});
