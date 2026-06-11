import { test, expect } from '@playwright/test';

test('Just check status of login response (no body read)', async ({ page }) => {
  test.setTimeout(60000);

  page.on('request', r => {
    if (r.url().includes('/api/') || r.url().includes('/sanctum/'))
      console.log(`[REQ ${r.method()}] ` + r.url().substring(0, 80));
  });
  page.on('response', r => {
    if (r.url().includes('/api/') || r.url().includes('/sanctum/'))
      console.log(`[RESP ${r.status()}] ` + r.url().substring(0, 80));
  });
  page.on('requestfailed', r => console.log(`[FAILED] ` + r.url().substring(0, 80)));

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.waitForTimeout(500);
  
  await page.click('button[type="submit"]');
  await page.waitForTimeout(8000);

  console.log('=== URL: ' + page.url() + ' ===');
});
