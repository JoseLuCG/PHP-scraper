<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;

class VividSeatsScraper
{
    private string $url;

    public function __construct(string $url) {
        $this->url = $url;

        $chromePath = getenv('CHROME_PATH');
        if (!$chromePath) {
            $chromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe';
        }
        putenv("CHROME_PATH=$chromePath");
    }

    public function scrape(): array {
        $browserFactory = new BrowserFactory();
        $browser = $browserFactory->createBrowser([
            'headless' => true,
            'windowSize' => [1920, 1080],
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'customFlags' => ['--disable-gpu', '--no-sandbox', '--disable-dev-shm-usage'],
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate($this->url)->waitForNavigation(Page::NETWORK_IDLE, 30000);

            $this->waitForListings($page);

            $this->scrollToLoadAll($page);

            $data = $page->evaluate($this->getExtractionScript())->getReturnValue();

            $listings = $data['listings'] ?? [];

            return [
                'success' => true,
                'event' => $data['event'] ?? [],
                'listings' => $listings,
                'total_listings' => count($listings),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } finally {
            try {
                $browser->close();
            } catch (\Throwable $e) {

            }
        }
    }

    private function waitForListings($page): void {
        $page->evaluate(<<<'JS'
        (async () => {
            const delay = ms => new Promise(r => setTimeout(r, ms));
            for (let i = 0; i < 30; i++) {
            if (document.querySelectorAll('[data-testid="listing-row-container"]').length > 0) return;
            await delay(200);
            }
        })();
        JS)->getReturnValue(10000);
    }

    private function scrollToLoadAll($page): void {
        $page->evaluate(<<<'JS'
        (async () => {
            const delay = ms => new Promise(r => setTimeout(r, ms));

            const container = document.querySelector('[class*="listingsContainer"], [data-testid="listing-container"]');
            if (!container) return;

            for (let i = 0; i < 15; i++) {
                const before = container.querySelectorAll('[data-testid="listing-row-container"]').length;

                container.scrollTop = container.scrollHeight;
                await delay(600);

                const after = container.querySelectorAll('[data-testid="listing-row-container"]').length;
            if (before === after) break;
            }
        })();
        JS)->getReturnValue(30000);
    }

    private function getExtractionScript(): string {
        return file_get_contents(__DIR__ . '/../javascript/extract-listings.js');
    }
}
