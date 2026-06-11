import { test, expect } from '@playwright/test';

test('Capture 404 URL and check Vue app health', async ({ page }) => {
  test.setTimeout(60000);

  // Use route to catch ALL failed responses
  const failures = [];
  page.on('response', r => {
    if (r.status() >= 400) failures.push({ status: r.status(), url: r.url().substring(0,150) });
  });

  // Also patch console.error to capture the 404 URL in browser
  await page.addInitScript(() => {
    const origError = console.error;
    console.error = function(...args) {
      window.__lastConsoleError = args.map(a => typeof a === 'string' ? a : '').join(' ');
      return origError.apply(this, args);
    };
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(3000);

  // Get console error details from browser
  const browserErrors = await page.evaluate(() => window.__lastConsoleError || 'none');
  console.log('=== BROWSER CONSOLE ERROR: ' + browserErrors.substring(0, 500) + ' ===');
  console.log('=== FAILURES: ' + JSON.stringify(failures) + ' ===');

  // Try submitting with Enter key instead of button click
  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');

  // Use keyboard Enter to submit
  await page.press('input[type="password"]', 'Enter');
  await page.waitForTimeout(5000);

  console.log('=== URL AFTER ENTER: ' + page.url() + ' ===');
});
