<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class ProjectSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ('local' !== app()->environment()) {
            $this->error('This command can only be run in the local environment');

            return self::INVALID;
        }

        $this->info('Installing dependencies...');
        exec('composer install');

        if (! file_exists('.env')) {
            copy('.env.example', '.env');
            $this->info('.env created');
        }

        $this->call('key:generate');
        $this->call('migrate');
        $this->call('optimize:clear');

        $this->info('Project setup complete 🚀');

        return self::SUCCESS;
    }
}
