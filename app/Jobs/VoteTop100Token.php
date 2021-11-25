<?php

namespace App\Jobs;

use App\Core\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 *
 */
class VoteTop100Token extends VoteJob
{
    /**
     * @var string|null
     */
    protected ?string $url = 'https://top100token.com/address/0x76e08e1c693d42551dd6ba7c2a659f74ff5ba261';

    /**
     * @param WebDriver $webDriver
     * @return bool
     */
    public function process(WebDriver $webDriver) : bool
    {
        Log::info('Browsing the page using the following ip address + browser details', [
            'url' => $this->url,
        ]);

        $webDriver->get($this->url);

        Log::debug('Searching for the vote button');

        $voteButton = $webDriver->findElement(WebDriverBy::cssSelector('button[status=primary]'));

        // vote check vote button
        if(Str::contains($voteButton->getText(), 'Vote')) {
            Log::info('Found the vote button clicking the button to cast my vote', [
                'button_id' => $voteButton->getID(),
                'text' => $voteButton->getText(),
            ]);

            $voteButton->click();

            // click and wait
            sleep(2);

            return true;
        }

        Log::debug('Closing the browser');

        $webDriver->closeBrowser();

        // return the result to the top level
        return false;
    }

    /**
     * @return string[]
     */
    public function tags()
    {
        return ['vote'];
    }
}
