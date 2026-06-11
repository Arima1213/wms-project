import { test, expect } from '@playwright/test';

test('Capture the exact error message and network', async ({ page }) => {
  test.setTimeout(60000);

  // Log ALL responses (no filter)
  page.on('response', async r => {
    console.log(`[ANY RESP ${r.status()}] ${r.url().substring(0,120)}`);
  });
  page.on('requestfailed', r => 
    console.log(`[FAIL ${r.method()}] ${r.url().substring(0,120)} ${r.failure()?.errorText}`));
  page.on('pageerror', err => console.log('[PAGE ERROR] ' + err.message));
  page.on('console', msg => console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`));

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(8000);

  // Read error text from the page
  const text = await page.evaluate(() => {
    const errDiv = document.querySelector('.bg-red-50');
    return errDiv ? errDiv.textContent : 'NO ERROR DIV';
  });
  console.log('=== ERROR TEXT: ' + text + ' ===');
  console.log('=== URL: ' + page.url() + ' ===');
});
