import { test, expect } from '@playwright/test';

test('Submit form programmatically via JS', async ({ page }) => {
  test.setTimeout(60000);

  page.on('request', r => {
    if (r.url().includes('/api/') || r.url().includes('/sanctum/'))
      console.log(`[REQ ${r.method()}] ${r.url()}`);
  });
  page.on('response', async r => {
    if (r.url().includes('/api/') || r.url().includes('/sanctum/')) {
      try { const b = await r.text(); console.log(`[RESP ${r.status()}] ${r.url().substr(-30)}`); } catch(e){}
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // Set form values via page.evaluate (bypasses Playwright fill)
  await page.evaluate(() => {
    const emailInput = document.querySelector('input[type="email"]');
    const passInput = document.querySelector('input[type="password"]');
    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
      window.HTMLInputElement.prototype, 'value'
    ).set;
    nativeInputValueSetter.call(emailInput, 'admin@wms.local');
    emailInput.dispatchEvent(new Event('input', { bubbles: true }));
    nativeInputValueSetter.call(passInput, 'password123');
    passInput.dispatchEvent(new Event('input', { bubbles: true }));
  });
  await page.waitForTimeout(500);

  // Click submit
  await page.click('button[type="submit"]');
  await page.waitForTimeout(8000);

  console.log('=== URL: ' + page.url() + ' ===');
});
