import { test, expect } from '@playwright/test';

test('Final debug: wait for login API and check Vue state', async ({ page }) => {
  test.setTimeout(60000);

  let loginRespReceived = false;

  page.on('console', msg => {
    if (msg.type() === 'error') console.log(`[CONSOLE ERROR] ${msg.text()}`);
    if (msg.text().includes('[Vue warn]')) console.log(`[VUE WARN] ${msg.text()}`);
    if (msg.text().includes('[Vue error]')) console.log(`[VUE ERROR] ${msg.text()}`);
    if (msg.text().includes('app error')) console.log(`[VUE APP ERROR] ${msg.text()}`);
  });
  page.on('pageerror', err => console.log(`[PAGE ERROR] ${err.message}`));

  // Wait for login API response
  const loginResp = page.waitForResponse(
    resp => resp.url().includes('/api/v1/login') && resp.status() === 200
  );

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');

  // Wait for login API to succeed
  await loginResp;
  console.log('=== LOGIN API RESPONDED 200 ===');

  // Wait a bit more for Vue to process
  await page.waitForTimeout(3000);

  // Check auth store state
  const state = await page.evaluate(() => {
    const appEl = document.querySelector('#app');
    if (!appEl || !appEl.__vue_app__) return { error: 'no vue app' };
    const pinia = appEl.__vue_app__.config.globalProperties.$pinia;
    if (!pinia) return { error: 'no pinia' };
    const authStore = pinia._s.get('auth');
    if (!authStore) return { error: 'no authStore', stores: Array.from(pinia._s.keys()) };
    return {
      isLoggedIn: authStore.isLoggedIn,
      token: authStore.token,
      loading: authStore.loading,
      userEmail: authStore.user?.data?.email || authStore.user?.email || null,
    };
  });
  console.log('=== AUTH STORE: ' + JSON.stringify(state) + ' ===');

  console.log('=== URL: ' + page.url() + ' ===');

  // Take a screenshot to show the page state
  await page.screenshot({ path: 'debug-login-state.png', fullPage: true });
  console.log('=== SCREENSHOT SAVED ===');
});
