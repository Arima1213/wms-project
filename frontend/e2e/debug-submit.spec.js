import { test, expect } from '@playwright/test';

test('Find the 404 and check if submit works', async ({ page }) => {
  test.setTimeout(60000);

  page.on('requestfailed', r => {
    console.log(`[FAILED REQ ${r.method()}] ${r.url()} - ${r.failure()?.errorText}`);
  });
  page.on('response', r => {
    if (r.status() >= 400) console.log(`[ERR RESP ${r.status()}] ${r.url()}`);
  });
  page.on('console', msg => {
    if (msg.type() === 'error' || msg.type() === 'warning') 
      console.log(`[CONSOLE ${msg.type()}] ${msg.text().substring(0,200)}`);
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(3000);

  // Check button state
  const btnState = await page.evaluate(() => {
    const btn = document.querySelector('button[type="submit"]');
    if (!btn) return { exists: false };
    return {
      exists: true,
      disabled: btn.disabled,
      text: btn.textContent,
      visible: btn.offsetParent !== null,
      rect: btn.getBoundingClientRect(),
    };
  });
  console.log('=== BUTTON STATE: ' + JSON.stringify(btnState) + ' ===');

  // Check input values after fill
  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  
  const inputState = await page.evaluate(() => {
    const email = document.querySelector('input[type="email"]');
    const pass = document.querySelector('input[type="password"]');
    return {
      emailVal: email ? email.value : 'not found',
      passVal: pass ? pass.value : 'not found',
    };
  });
  console.log('=== INPUT STATE: ' + JSON.stringify(inputState) + ' ===');

  // Click submit
  await page.click('button[type="submit"]');
  await page.waitForTimeout(5000);

  // Check if any API calls were made
  console.log('=== URL: ' + page.url() + ' ===');
  
  // Check for any visible changes
  const html = await page.content();
  const hasToast = html.includes('toast') || html.includes('Toast');
  console.log('=== HAS TOAST: ' + hasToast + ' ===');
});
