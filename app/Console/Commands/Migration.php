<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Migration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migration {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shortcut for creating migration files.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        exec("php artisan make:migration $name");
    }
}
