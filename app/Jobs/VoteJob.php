<?php

namespace App\Jobs;

use App\Core\WebDriver;
use App\Domain\Models\Vote;
use App\Exceptions\WebDriver\IpHasAlreadyBeenUsedException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 *
 */
abstract class VoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var WebDriver
     */
    protected WebDriver $webDriver;

    /**
     * @var string|null
     */
    protected ?string $currentIp = null;

    /**
     * @var string|null
     */
    protected ?string $url = null;

    /**
     * @param WebDriver $webDriver
     * @return bool
     * @throws IpHasAlreadyBeenUsedException
     */
    public function handle(WebDriver $webDriver)
    {
        $this->webDriver = $webDriver;

        if($webDriver->proxyIsEnabled()) {
            $this->setCurrentIp($webDriver->getProxyIp());

            if($this->ipHasAlreadyBeenUsed($this->getCurrentIp())) {
                $webDriver->quit();
                throw new IpHasAlreadyBeenUsedException('The ip address has already been used');
            }
        }

        // process it and get the result
        $result = $this->process($webDriver);
        if($result === true) {

            // generate a uuid
            $randomUuid = (string) Str::uuid();
            $path = 'app/public/screenshots/'.$randomUuid.'.png';

            // create a screenshot of the vote
            $webDriver->takeScreenshot(storage_path($path));

            /** @var Vote $vote */
            $vote = $this->getBuilder()->create([
                'ip' => $this->getCurrentIp(),
                'user_agent' => $webDriver->getUserAgent(),
                'image' => $randomUuid,
                'type' => $this::class,
            ]);

            Log::info('Storing the vote inside the database', [
                'vote_id' => $vote->getKey(),
                'type' => $this::class,
            ]);

            // close the browser
            $webDriver->closeBrowser();

            return true;
        }

        // release the job back to the queue
        $this->release(now()->addSeconds(15));


        return false;
    }

    /**
     * @return string|null
     */
    private function getCurrentIp() :? string
    {
        return $this->currentIp;
    }

    /**
     * @param string $ip
     */
    private function setCurrentIp(string $ip)
    {
        $this->currentIp = $ip;

        Log::withContext(['proxy_ip' => $ip]);
    }

    /**
     * @param WebDriver $webDriver
     * @return bool
     */
    public abstract function process(WebDriver $webDriver) : bool;

    /**
     * @return Builder
     */
    private function getBuilder() : Builder
    {
        return Vote::query()->where('type', $this::class);
    }

    /**
     * @param $ip
     * @return bool
     */
    private function ipHasAlreadyBeenUsed($ip) : bool
    {
        return $this->getBuilder()->where('ip', $ip)->exists();
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        // quit the webdriver
        if(isset($this->webDriver)) {
            $this->webDriver->closeBrowser();
        }

        if($exception instanceof IpHasAlreadyBeenUsedException) {
            $this->release(now()->addSeconds(15));
        }
    }
}
