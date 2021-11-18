<?php

namespace App\Jobs;

use App\Domain\Models\Vote;
use Campo\UserAgent;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Illuminate\Support\Facades\Cache;

/**
 *
 */
class VoteTop100Token implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected string $url = 'https://top100token.com/address/0x76e08e1c693d42551dd6ba7c2a659f74ff5ba261';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::debug('Starting chrome browser and browsing', [
            'url' => $this->url
        ]);

        $userAgent = UserAgent::random([
            'device_type' => ['Tablet', 'Desktop', 'Mobile']
        ]);

        $desiredCapabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments([
            '--user-agent=' . $userAgent
        ]);

        $desiredCapabilities->setCapability(
            WebDriverCapabilityType::PROXY, [
                'proxyType' => 'manual',
                'httpProxy' => '37.97.209.95:24000',
                'sslProxy' => '37.97.209.95:24000',
            ]
        );

        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create(config('webdriver.host'), $desiredCapabilities);

        // check the current ip of the browser
        $ipText = $driver->get('https://api.ipify.org/?format=json');
        $ip = null;
        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $ipText->getPageSource(), $match)) {
            if (filter_var($match[0], FILTER_VALIDATE_IP)) {
                // we have an IP
                $ip = $match[0];
            }
        }

        if(Vote::query()->where('ip', $ip)->exists()) {
            Log::error('Ip address has already been used once retrying again', [
                'ip' => $ip,
            ]);

            // quit the driver and exit
            $driver->quit();

            // retry the handle
            return $this->handle();
        }

        Log::debug('Clearing all browser cookies to be sure that we don\'t have any cookies');
        // clear all the cookies
        $driver->manage()->deleteAllCookies();

        Log::info('Browsing the page using the following ip address + browser details', [
            'url' => $this->url,
            'ip' => $ip
        ]);

        $driver->get($this->url);

        Log::debug('Searching for the vote button');

        $voteButton = $driver->findElement(WebDriverBy::cssSelector('button[status=primary]'));

        // vote check vote button
        if(Str::contains($voteButton->getText(), 'Vote')) {
            Log::info('Found the vote button clicking the button to cast my vote', [
                'button_id' => $voteButton->getID(),
                'text' => $voteButton->getText(),
            ]);

            $voteButton->click();

            // click and wait
            sleep(2);

            $randomUuid = (string) Str::uuid();
            $path = 'app/public/screenshots/'.$randomUuid.'.png';

            // create a screenshot of the vote
            $driver->takeScreenshot(storage_path($path));

            Vote::query()->create([
                'ip' => $ip,
                'user_agent' => $userAgent,
                'image' => $randomUuid
            ]);
        }

        Log::debug('Closing the browser');

        $driver->quit();

        return 0;
    }

    /**
     * @return string[]
     */
    public function tags()
    {
        return ['vote'];
    }
}
