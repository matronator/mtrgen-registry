<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Appwrite\Client;
use Appwrite\Services\Storage;
use Appwrite\Services\Tokens;

class AppwriteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            $client = new Client();

            $client->setEndpoint(env('APPWRITE_ENDPOINT'))
                ->setProject(env('APPWRITE_PROJECT_ID'))
                ->setKey(env('APPWRITE_API_KEY'));

            return $client;
        });
    }
}
