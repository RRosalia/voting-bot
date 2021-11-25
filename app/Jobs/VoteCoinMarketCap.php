<?php

namespace App\Jobs;

use App\Core\WebDriver;
use Carbon\Carbon;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
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
     * @param WebDriver $webDriver
     * @return bool
     */
    public function process(WebDriver $webDriver) : bool
    {
        Log::info('Browsing the page using the following ip address + browser details', [
            'url' => $this->url,
        ]);

        sleep(mt_rand(0, 1));

        $webDriver->get($this->url);

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
