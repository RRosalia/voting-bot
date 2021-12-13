<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 *
 */
class RetryAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:retry-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release all failed-jobs onto the queue.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $failed = $this->laravel['queue.failer']->all();

        if (! empty($failed)) {
            collect($failed)->each(function($value) {
                $this->call('queue:retry', ['id' => $value->id]);
            });
        } else {
            $this->error('No failed jobs.');
        }
    }
}
