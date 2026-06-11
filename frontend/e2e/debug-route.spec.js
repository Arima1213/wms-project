import { test, expect } from '@playwright/test';

test('Route-intercept login to see real browser response', async ({ page }) => {
  test.setTimeout(60000);

  // Intercept using route() to catch EVERYTHING before browser processes it
  await page.route('**/api/v1/login', async (route) => {
    console.log('[ROUTE INTERCEPT] login request intercepted!');
    try {
      const response = await page.request.fetch(route.request());
      const body = await response.text();
      console.log(`[ROUTE RESP] status=${response.status()} body=${body.substring(0, 300)}`);
      await route.fulfill({ response });
    } catch (e) {
      console.log('[ROUTE ERROR] ' + e.message);
      await route.continue();
    }
  });
  
  // Also intercept CSRF
  await page.route('**/sanctum/csrf-cookie', async (route) => {
    console.log('[ROUTE INTERCEPT] CSRF request!');
    try {
      const response = await page.request.fetch(route.request());
      console.log(`[ROUTE CSRF RESP] status=${response.status()}`);
      await route.fulfill({ response });
    } catch (e) {
      console.log('[ROUTE CSRF ERROR] ' + e.message);
      await route.continue();
    }
  });

  page.on('console', msg => {
    if (msg.type() === 'error') console.log(`[CONSOLE ERR] ${msg.text()}`);
  });
  // Catch unhandled rejections
  page.on('pageerror', err => console.log('[PAGE ERROR] ' + err.message));

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(8000);

  console.log('=== FINAL URL: ' + page.url() + ' ===');
});
