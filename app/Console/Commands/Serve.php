<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Serve extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve {port=8000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start PHP server on port 8000';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $port = $this->argument('port');
        exec('php -S localhost:'. escapeshellarg($port) .' -t public');
    }
}
