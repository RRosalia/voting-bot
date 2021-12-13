<?php

namespace App\Core;

use Campo\UserAgent;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Illuminate\Support\Facades\Log;

/**
 * @m
 */
class WebDriver extends RemoteWebDriver
{
    /**
     * @var string|null
     */
    protected ?string $currentIp = null;

    /**
     * @param string $webdriverHost
     * @param string|null $proxy
     * @return RemoteWebDriver
     * @throws \Exception
     */
    public static function instantiate(string $webdriverHost, ?string $proxy = null) : RemoteWebDriver
    {
        $userAgent = UserAgent::random([
            'device_type' => ['Tablet', 'Desktop', 'Mobile']
        ]);

        Log::withContext([
            'user_agent' => $userAgent
        ]);

        $desiredCapabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments([
            '--user-agent=' . $userAgent,
            '--headless'
        ]);

        if($proxy) {
            $desiredCapabilities->setCapability(
                WebDriverCapabilityType::PROXY, [
                    'proxyType' => 'manual',
                    'httpProxy' => "$proxy",
                    'sslProxy' => "$proxy",
                ]
            );
        }

        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        return WebDriver::create($webdriverHost, $desiredCapabilities);
    }

    /**
     *
     */
    public function closeBrowser() : void
    {
        $this->quit();
    }

    /**
     * @return bool
     */
    public function proxyIsEnabled() : bool
    {
        $settings = $this->getCapabilities()->getCapability(WebDriverCapabilityType::PROXY);

        return (count($settings) > 0);
    }

    /**
     * @return string
     */
    public function getUserAgent() : string
    {
        return $this->executeScript('return navigator.userAgent;');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getIp() : string
    {
        if($this->currentIp) {
            return $this->currentIp;
        }

        // check the current ip of the browser
        $ipText = $this->get('https://api.ipify.org/?format=json');
        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $ipText->getPageSource(), $match)) {
            if (filter_var($match[0], FILTER_VALIDATE_IP)) {
                $this->currentIp = $match[0];
                // we have an IP
                return $match[0];
            }
        }

        throw new \Exception('Failed to resolve ip address');
    }


    /**
     * @return string|null
     * @throws \Exception
     */
    public function getProxyIp() :? string
    {
        if($this->proxyIsEnabled() === false) {
            return null;
        }

        return $this->getIp();
    }
}
