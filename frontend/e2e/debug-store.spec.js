import { test, expect } from '@playwright/test';

test('Debug auth store state after login submit', async ({ page }) => {
  test.setTimeout(60000);

  page.on('console', msg => {
    if (msg.type() === 'error') console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });
  page.on('request', r => {
    if (r.url().includes('/sanctum/') || r.url().includes('/api/'))
      console.log(`[REQ ${r.method()}] ${r.url()}`);
  });
  page.on('response', async r => {
    if (r.url().includes('/sanctum/') || r.url().includes('/api/')) {
      const body = await r.text();
      console.log(`[RESP ${r.status()}] ${r.url()} Body:${body.substring(0,150)}`);
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  
  // Intercept: check router state before and after
  await page.evaluate(() => {
    window.__lastRouteChange = null;
    const origPush = window.__router?.push;
    // Intercept router.push to log
  });

  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  // Check Pinia auth store state
  const storeState = await page.evaluate(async () => {
    // Access the Pinia store directly
    const pinia = window.__vue_app__?.config?.globalProperties?.$pinia;
    if (!pinia) return { error: 'No Pinia found', keys: Object.keys(window).filter(k => k.startsWith('__')) };
    
    // Try to find auth store
    const stores = pinia._s;
    const authStore = stores.get('auth');
    if (authStore) {
      return {
        isLoggedIn: authStore.isLoggedIn,
        token: authStore.token,
        user: authStore.user ? JSON.stringify(authStore.user).substring(0, 100) : null,
        loading: authStore.loading,
      };
    }
    
    // List available stores
    const storeNames = Array.from(stores.keys());
    return { error: 'No auth store', stores: storeNames };
  });
  console.log('=== AUTH STORE STATE: ' + JSON.stringify(storeState) + ' ===');

  // Also check if there's an active router guard issue
  const urlAfter = await page.evaluate(() => window.location.href);
  console.log('=== URL AFTER SUBMIT: ' + urlAfter + ' ===');
});
