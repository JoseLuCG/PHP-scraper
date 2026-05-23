<?php

require_once __DIR__ . '/../vendor/autoload.php';

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;

class VividSeatsScraper
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;

        $chromePath = getenv('CHROME_PATH');
        if (!$chromePath) {
            $chromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe';
        }
        putenv("CHROME_PATH=$chromePath");
    }

    public function scrape(): array
    {
        $browserFactory = new BrowserFactory();
        $browser = $browserFactory->createBrowser([
            'headless' => true,
            'windowSize' => [1920, 1080],
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'customFlags' => ['--disable-gpu', '--no-sandbox'],
        ]);

        try {
            $page = $browser->createPage();
            $page->navigate($this->url)->waitForNavigation(Page::DOM_CONTENT_LOADED, 30000);
            // Brief pause for React hydration / client-side render
            sleep(2);

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
                // Chrome might already be dead — ignore close errors
            }
        }
    }

    private function scrollToLoadAll($page): void
    {
        $page->evaluate(<<<'JS'
(async () => {
    const delay = ms => new Promise(r => setTimeout(r, ms));

    // Find the scrollable listing container
    const container = document.querySelector('[class*="listingsContainer"], [data-testid="listing-container"]');
    if (!container) {
        // Fallback: try the whole document body
        const rows = document.querySelectorAll('[data-testid="listing-row-container"]');
        if (rows.length) return; // already visible
        return;
    }

    for (let i = 0; i < 20; i++) {
        const before = container.querySelectorAll('[data-testid="listing-row-container"]').length;

        container.scrollTop = container.scrollHeight;
        await delay(1200);

        const after = container.querySelectorAll('[data-testid="listing-row-container"]').length;
        if (before === after) break;
    }
})();
JS
        )->getReturnValue(60000);
    }

    private function getExtractionScript(): string
    {
        return <<<'JS'
(function() {
    const nextData = document.getElementById('__NEXT_DATA__');
    const props = nextData ? JSON.parse(nextData.textContent).props?.pageProps || {} : {};
    const prod = props.initialProductionDetailsData?.data || {};

    const rowElements = document.querySelectorAll('[data-testid="listing-row-container"]');
    const seen = new Set();
    const listings = [];

    rowElements.forEach(row => {
        const listingDiv = row.querySelector('[data-testid]');
        if (!listingDiv) return;

        const listingId = listingDiv.getAttribute('data-testid') || '';
        if (seen.has(listingId)) return;
        seen.add(listingId);

        // Section: find the div inside sectionContent whose data-testid is the section name
        let section = '';
        const sectionContent = row.querySelector('[class*="sectionContent"]');
        if (sectionContent) {
            const tidDivs = sectionContent.querySelectorAll('div[data-testid]');
            tidDivs.forEach(div => {
                const tid = div.getAttribute('data-testid') || '';
                if (tid && !tid.startsWith('row') && !tid.startsWith('seat') && !tid.startsWith('3ddv') && !tid.startsWith('badge') && tid.length > 3) {
                    section = tid;
                }
            });
        }

        const rowTextEl = row.querySelector('[data-testid="row"]');
        const rowText = rowTextEl ? rowTextEl.textContent.trim().replace('Row ', '') : '';

        const priceEl = row.querySelector('[data-testid="listing-price"]');
        const priceText = priceEl ? priceEl.textContent.trim() : '0';
        const price = parseInt(priceText.replace(/[^0-9]/g, '')) || 0;

        const discountEl = row.querySelector('[data-testid="discount-price"]');
        let discountPrice = null;
        if (discountEl) {
            const dText = discountEl.textContent.trim();
            discountPrice = parseInt(dText.replace(/[^0-9]/g, '')) || null;
        }

        const scoreEl = row.querySelector('[data-testid^="deal-score-"]');
        const scoreText = scoreEl ? scoreEl.textContent.trim() : '0';
        const scoreParts = scoreText.match(/^([\d.]+)/);
        const dealScore = scoreParts ? parseFloat(scoreParts[1]) : 0;

        const text = row.textContent;
        const qtyMatch = text.match(/(\d+)(?:–(\d+))?\s*tickets?/);
        let qtyMin = 0, qtyMax = 0;
        if (qtyMatch) {
            qtyMin = parseInt(qtyMatch[1]);
            qtyMax = qtyMatch[2] ? parseInt(qtyMatch[2]) : qtyMin;
        }

        const badgeEl = row.querySelector('[data-testid*="badge-"]');
        const badge = badgeEl ? badgeEl.textContent.trim() : '';

        listings.push({
            section: section,
            row: rowText,
            price: discountPrice || price,
            originalPrice: discountPrice ? price : null,
            quantityMin: qtyMin,
            quantityMax: qtyMax,
            dealScore: dealScore,
            badge: badge,
        });
    });

    return {
        event: {
            name: prod.name || '',
            venue: prod.venue?.name || '',
            venueAddress: prod.venue?.address1 || '',
            venueCity: prod.venue?.city || '',
            venueState: prod.venue?.state || '',
            date: prod.formattedDate?.date || '',
            time: prod.formattedDate?.time || '',
            totalListings: prod.listingCount || 0,
            totalTickets: prod.ticketCount || 0,
            minPrice: prod.minPrice || 0,
            maxPrice: prod.maxPrice || 0,
            avgPrice: prod.avgPrice || 0,
        },
        listings: listings,
    };
})();
JS;
    }
}
