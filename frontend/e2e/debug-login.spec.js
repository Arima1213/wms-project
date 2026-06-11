import { test, expect } from '@playwright/test';

test('debug login flow', async ({ page }) => {
  test.setTimeout(60000);

  // Log all console output
  page.on('console', msg => {
    console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });

  // Log all API responses
  page.on('response', async response => {
    const url = response.url();
    if (url.includes('/api/v1/') || url.includes('/sanctum/')) {
      console.log(`[RESP ${response.status()}] ${url}`);
      if (response.status() >= 400) {
        try {
          console.log('  Body:', await response.text());
        } catch(e) {}
      }
    }
  });

  page.on('request', request => {
    const url = request.url();
    if (url.includes('/api/v1/') || url.includes('/sanctum/')) {
      console.log(`[REQ ${request.method()}] ${url}`);
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);
  
  // See what the page looks like
  const html = await page.content();
  console.log('=== PAGE HTML (first 3000 chars) ===');
  console.log(html.substring(0, 3000));
  
  // Try to fill and submit
  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  
  // Wait a bit before clicking submit
  await page.waitForTimeout(500);
  
  await page.click('button[type="submit"]');
  
  // Wait and check if URL changed
  await page.waitForTimeout(5000);
  console.log('=== FINAL URL: ' + page.url() + ' ===');
});
