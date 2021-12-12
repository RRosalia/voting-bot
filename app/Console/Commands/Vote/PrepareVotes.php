<?php

namespace App\Console\Commands\Vote;

use App\Jobs\VoteCoinMarketCap;
use App\Jobs\VoteJob;
use App\Jobs\VoteTop100Token;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\ClassString;

/**
 *
 */
class PrepareVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepare:votes';

    /**
     * @var array|\int[][]
     */
    protected array $frequency = [
        VoteTop100Token::class => [
           'min' => 100,
           'max' => 400,
        ],
        VoteCoinMarketCap::class => [
            'min' => 500,
            'max' => 1200,
        ],
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put votes inside the queue for processing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * @var VoteJob $job
         * @var  $frequency
         */
        foreach ($this->frequency as $job => $frequency) {
            $votesPerHour = rand($frequency['min'], $frequency['max']);

            Log::info('Votes per hour', [
                'votes' => $votesPerHour,
                'job' => $job
            ]);

            for ($x = 0; $x <= $votesPerHour; $x++) {
                $delay = Carbon::now()->addMinutes(rand(0, 60));
                $job::dispatch()->delay($delay);
            }
        }

        return 0;
    }
}
