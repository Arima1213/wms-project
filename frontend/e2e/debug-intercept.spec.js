import { test, expect } from '@playwright/test';

test('Intercept login API call to check exact response', async ({ page }) => {
  test.setTimeout(60000);

  // Intercept ALL API responses
  page.on('response', async response => {
    const url = response.url();
    if (url.includes('/api/')) {
      const body = await response.text();
      console.log(`[RESP ${response.status()}] ${url} => ${body.substring(0, 300)}`);
    }
  });
  page.on('requestfailed', r => console.log(`[FAIL] ${r.url()} ${r.failure()?.errorText}`));
  page.on('console', msg => {
    if (msg.type() === 'error') console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(5000);

  const html = await page.content();
  const hasError = html.includes('bg-red-50');
  const errorText = hasError ? html.match(/bg-red-50[^]*?div[^]*?>([^<]+)/)?.[1] || 'error found' : 'no error';
  console.log('=== ERROR UI: ' + hasError + ', Text: ' + errorText + ' ===');
  console.log('=== URL: ' + page.url() + ' ===');
});
