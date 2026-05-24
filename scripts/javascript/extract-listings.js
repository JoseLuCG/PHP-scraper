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
