<?php

namespace App\Jobs;

use App\Core\WebDriver;
use Carbon\Carbon;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 *
 */
class VoteCoinMarketCap extends VoteJob
{
    /**
     * @var string|null
     */
    protected ?string $url = 'https://coinmarketcap.com/currencies/shakita-inu/';

    /**
     * Start the search from one of those random pages
     *
     * @var array|string[]
     */
    protected array $searchFrom = [
        'https://coinmarketcap.com/',
        'https://coinmarketcap.com/new/',
        'https://coinmarketcap.com/best-cryptos/'
    ];

    /**
     * @param WebDriver $webDriver
     * @return bool
     */
    public function process(WebDriver $webDriver) : bool
    {
        $shouldSearch = (rand(0, 10) > 3); // 70% we execute search
        $shouldVote = (rand(0, 10) > 3); // 70% of the time we vote

        $shouldSearch = true;
        $shouldVote = false;

        Log::info('Started CoinMarketCap Processor', [
            'url' => $this->url,
            'should_vote' => $shouldVote,
            'should_search' => $shouldSearch,
        ]);

        if($shouldSearch === true) {
            $randomPage = $this->searchFrom[array_rand($this->searchFrom)];
            Log::info('Starting the search', [
                'page' => $randomPage
            ]);
            $webDriver->get($randomPage);

            sleep(rand(0, 2));

            $searchBox = WebDriverBy::cssSelector('.cmc-header-mobile svg');
            $searchBox = $webDriver->findElement($searchBox);

            // click on the search box
            $searchBox->click();

            sleep(rand(1, 2));

            $webDriver->wait(10, 200)->until(function(WebDriver $driver){
                return !is_null($driver->findElement(WebDriverBy::cssSelector('.enter-done input')));
            });

            $webDriver->findElement(WebDriverBy::cssSelector('.enter-done input'))->click();

            $letters = rand(4, 9);
            $word = substr('shakita inu', 0, $letters);
            Log::info('Typing the first letters', compact('letters', 'word'));

            $webDriver->getKeyboard()->sendKeys($word);

            // click on shakita inu
            $links = WebDriverBy::cssSelector('.enter-done a.cmc-link');

            $webDriver->wait(10, 500)->until(function ($driver) use ($links) {
                $foundLinks = $driver->findElements($links);
                // no search results
                if(count($foundLinks) === 0) {
                    return false;
                }
                return collect($foundLinks)->first(function(WebDriverElement $element){
                    return Str::contains($element->getText(), 'Shakita');
                }) !== null;
            });

            $links = $webDriver->findElements($links);

            /** @var WebDriverElement $button */
            $button = collect($links)->firstOrFail(function(WebDriverElement $element){
                return Str::contains($element->getText(), 'Shakita');
            });

            sleep(rand(1, 3));

            $button->click();

            // now verify thar url is the same
            if($webDriver->getCurrentURL() !== $this->url) {
                Log::error('Url is not the shakita url error out', [
                    'url' => $webDriver->getCurrentURL(),
                ]);
                return false;
            }

            Log::info('Storing the search inside the database');

            DB::table('searches')->insert([
                'type' => get_class(),
                'ip' => $webDriver->getIp(),
                'user_agent' => $webDriver->getUserAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } else {
            $webDriver->get($this->url);
        }

        if($shouldVote === false) {
            return true;
        }

        sleep(mt_rand(0, 1));

        do {
            $selector = WebDriverBy::cssSelector('button');

            /** @var WebDriverElement $button */
            $button = collect($webDriver->findElements($selector))->first(function(WebDriverElement $element){
                return Str::endsWith($element->getText(), 'Good');
            });

            // scroll randomly a bit down
            $totalHeightToScroll = mt_rand(100, 300);

            for ($x = 0; $x <= $totalHeightToScroll; $x++) {
                $randomScrollHeight = mt_rand(1, 3);
                // scroll to the location
                $webDriver->executeScript('window.scrollBy(0,'.$randomScrollHeight.')');
            }

            sleep(mt_rand(2, 3));


            if($webDriver->executeScript('return (window.innerHeight + window.scrollY) >= document.body.offsetHeight')) {
                Log::error('Scrolled to the bottom of the page and we did not our button');
                $webDriver->closeBrowser();
                return false;
            }

        }while(is_null($button));


        // we scroll further a random amount
        $totalHeightToScroll = mt_rand(0, 100);

        // scroll lower
        for ($x = 0; $x <= $totalHeightToScroll; $x++) {
            $randomScrollHeight = mt_rand(0, 1);
            // scroll to the location
            $webDriver->executeScript('window.scrollBy(0,'.$randomScrollHeight.')');
        }

        $button->click();

        sleep(5);

        return true;
    }

}
