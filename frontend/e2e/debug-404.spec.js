import { test, expect } from '@playwright/test';

test('Debug 404 + login flow', async ({ page }) => {
  test.setTimeout(60000);

  // Log ALL failed requests with their URLs
  page.on('requestfailed', request => {
    console.log(`[REQ FAILED] ${request.url()} [${request.method()}] - ${request.failure()?.errorText}`);
  });
  
  // Log all 4xx/5xx responses
  page.on('response', response => {
    if (response.status() >= 400) {
      console.log(`[RESP ${response.status()}] ${response.url()}`);
    }
  });

  // Log requests to sanctum/api
  page.on('request', request => {
    if (request.url().includes('/sanctum/') || request.url().includes('/api/'))
      console.log(`[REQ ${request.method()}] ${request.url()}`);
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // Log all resource performance entries for 404 finding
  const resources = await page.evaluate(() => 
    performance.getEntriesByType('resource')
      .map(e => ({ name: e.name.substring(0, 100), status: e.transferSize > 0 ? 'ok' : 'cached' }))
  );
  console.log('=== RESOURCES LOADED: ' + resources.length + ' ===');

  // Check page for any JS errors
  const errors = await page.evaluate(() => {
    return window.__VUE_DEVTOOLS_GLOBAL_HOOK__ ? 'devtools detected' : 'no devtools';
  });
  console.log('=== DEVTOOLS: ' + errors + ' ===');

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.waitForTimeout(500);
  
  // Set up a promise to capture URL changes
  await page.evaluate(() => {
    window.__urlChanged = false;
    const origPushState = history.pushState;
    history.pushState = function() {
      window.__urlChanged = true;
      return origPushState.apply(this, arguments);
    };
  });

  await page.click('button[type="submit"]');
  await page.waitForTimeout(8000);

  const urlInfo = await page.evaluate(() => ({
    url: window.location.href,
    urlChanged: window.__urlChanged,
  }));
  console.log('=== URL INFO: ' + JSON.stringify(urlInfo) + ' ===');
  
  // Try to interact with the page after login
  const pageHtml = await page.content();
  const hasError = pageHtml.includes('bg-red-50');
  console.log('=== HAS ERROR UI: ' + hasError + ' ===');
});
