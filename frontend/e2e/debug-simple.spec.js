import { test, expect } from '@playwright/test';

test('Simple: fill, wait, click submit', async ({ page }) => {
  test.setTimeout(60000);

  page.on('request', r => {
    if (r.url().includes('/sanctum/') || r.url().includes('/api/'))
      console.log(`[REQ ${r.method()}] ${r.url()}`);
  });
  page.on('requestfailed', r => console.log(`[FAIL] ${r.url()}`));
  page.on('console', msg => {
    if (msg.type() === 'error') console.log(`[CONSOLE ERR] ${msg.text()}`);
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // Fill and WAIT before submit
  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.waitForTimeout(500);
  
  console.log('=== CLICKING SUBMIT ===');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(8000);
  
  console.log('=== URL: ' + page.url() + ' ===');
});
