import { test, expect } from '@playwright/test';

test('Debug Vue auth store login flow', async ({ page }) => {
  test.setTimeout(60000);

  // Log ALL console messages
  page.on('console', msg => console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`));
  page.on('request', r => {
    if (r.url().includes('127.0.0.1') || r.url().includes('localhost:5173') || r.url().includes('localhost:8000'))
      console.log(`[REQ ${r.method()}] ${r.url()}`);
  });
  page.on('requestfailed', r => console.log(`[REQ FAIL] ${r.url()} - ${r.failure()?.errorText}`));
  page.on('response', async r => {
    if (r.status() >= 400 || r.url().includes('/sanctum/') || r.url().includes('/api/')) {
      const body = r.status() >= 400 ? (await r.text()).substring(0, 200) : '';
      console.log(`[RESP ${r.status()}] ${r.url()} ${body}`);
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // Check if app loaded properly
  const title = await page.title();
  const html = await page.content();
  console.log('=== PAGE LOADED: title=' + title + ' ===');
  
  // Check if Vue app is mounted
  const hasLoginForm = html.includes('Login');
  const hasEmailInput = html.includes('type="email"');
  console.log('=== Has login form: ' + hasLoginForm + ', Has email input: ' + hasEmailInput + ' ===');

  // Fill form like the test does
  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.waitForTimeout(500);

  // Capture state BEFORE clicking submit
  const beforeState = await page.evaluate(() => ({
    url: window.location.href,
  }));
  console.log('=== BEFORE SUBMIT: ' + JSON.stringify(beforeState) + ' ===');

  // Click submit
  await page.click('button[type="submit"]');
  
  // Wait for potential navigation
  await page.waitForTimeout(8000);
  
  const afterState = await page.evaluate(() => ({
    url: window.location.href,
    // Check if any error element appeared
    errorVisible: document.querySelector('.bg-red-50')?.textContent || 'none',
  }));
  console.log('=== AFTER SUBMIT: ' + JSON.stringify(afterState) + ' ===');
  
  // Try to capture the actual API call responses by monitoring network
  const networkLogs = await page.evaluate(() => {
    return performance.getEntriesByType('resource')
      .filter(e => e.name.includes('/sanctum/') || e.name.includes('/api/'))
      .map(e => e.name.substring(0, 100));
  });
  console.log('=== NETWORK LOGS: ' + JSON.stringify(networkLogs) + ' ===');
});
