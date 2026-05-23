import { chromium } from 'playwright';

const url = process.argv[2];
if (!url) {
  console.error('Usage: node scraper.mjs <url>');
  process.exit(1);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
  locale: 'en-US',
});

const page = await context.newPage();

const apiResponses = [];
page.on('response', async (response) => {
  const ct = response.headers()['content-type'] || '';
  if (ct.includes('json')) {
    try {
      const body = await response.text();
      if (body.length > 50 && body.length < 5000000) {
        apiResponses.push({ url: response.url(), body });
      }
    } catch {}
  }
});

await page.goto(url, { waitUntil: 'load', timeout: 60000 });
await page.waitForTimeout(3000);

// Try clicking interactive elements to trigger lazy-loaded data
for (const selector of ['button:has-text("1")', 'button:has-text("2")', 'button', 'select']) {
  try {
    const el = page.locator(selector).first();
    if (await el.isVisible({ timeout: 300 }).catch(() => false)) {
      await el.click({ timeout: 1000 }).catch(() => {});
      await page.waitForTimeout(2000);
    }
  } catch {}
}

await page.waitForTimeout(2000);

const nextData = await page.evaluate(() => {
  const script = document.querySelector('script#__NEXT_DATA__');
  return script ? JSON.parse(script.textContent) : null;
});

await browser.close();

// Extract ticket listings from all JSON sources
const listings = [];
const seen = new Set();

function extractListings(obj, depth = 0) {
  if (!obj || typeof obj !== 'object' || depth > 10) return;
  if (Array.isArray(obj)) {
    if (obj.length > 0 && typeof obj[0] === 'object' && obj[0] !== null) {
      const k = Object.keys(obj[0]);
      const kl = k.map(x => x.toLowerCase());
      const isHermesFormat = kl.includes('s') && kl.includes('p') && k.includes('allInPricePerTicket');
      const isStandardFormat = kl.some(x => x.includes('section')) && kl.some(x => x.includes('row') || x.includes('price'));

      if (isHermesFormat || isStandardFormat) {
        for (const item of obj) {
          let section, row, price, qty;
          if (item.s !== undefined && item.allInPricePerTicket !== undefined) {
            section = item.sectionName ?? item.s ?? '';
            row = item.row ?? item.r ?? '';
            price = item.allInPricePerTicket ?? item.p ?? '';
            qty = item.quantity ?? item.q ?? 1;
          } else {
            section = item.sectionName ?? item.section ?? item.zone ?? '';
            row = item.rowName ?? item.row ?? '';
            price = item.unitPrice ?? item.price ?? item.totalPrice ?? item.displayPrice ?? '';
            qty = item.quantity ?? item.availableQty ?? item.qty ?? 1;
          }
          const sig = `${section}|${row}|${price}`;
          if (!seen.has(sig) && section) {
            seen.add(sig);
            listings.push({
              section: String(section),
              row: String(row || '—'),
              price: String(price || '—'),
              quantity: qty,
            });
          }
        }
        return;
      }
    }
    for (const item of obj) extractListings(item, depth + 1);
  } else {
    for (const val of Object.values(obj)) extractListings(val, depth + 1);
  }
}

for (const { body } of apiResponses) {
  try { extractListings(JSON.parse(body)); } catch {}
}

extractListings(nextData);

// Fallback: top deals from __NEXT_DATA__
if (listings.length === 0 && nextData?.props?.pageProps?.initialTopDealListingsData?.data?.topDeals) {
  for (const deal of nextData.props.pageProps.initialTopDealListingsData.data.topDeals) {
    const section = String(deal.section || '');
    const sig = `${section}|${deal.row}|${deal.price}`;
    if (!seen.has(sig) && section) {
      seen.add(sig);
      listings.push({
        section,
        row: String(deal.row || '—'),
        price: String(deal.price || '—'),
        quantity: 1,
      });
    }
  }
}

const result = { status: 'ok', url, nextData, listings };
process.stdout.write(JSON.stringify(result));
