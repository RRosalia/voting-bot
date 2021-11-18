<?php

namespace App\Console\Commands\Vote;

use App\Jobs\VoteTop100Token;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class StartTop100Token extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vote:cast:top100';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $votesPerHour = rand(20, 80);

        Log::info('Votes per hour', [
            'votes' => $votesPerHour,
        ]);

        for ($x = 0; $x <= $votesPerHour; $x++) {
            $delay = Carbon::now()->addMinutes(rand(0, 60));

            Log::info('Delaying vote until', ['time' => $delay,]);

            VoteTop100Token::dispatch()->delay($delay);
        }

        return 0;
    }
}
