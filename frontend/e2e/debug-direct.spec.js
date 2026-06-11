import { test, expect } from '@playwright/test';

test('Direct: call authStore.login from browser console', async ({ page }) => {
  test.setTimeout(60000);

  page.on('request', r => {
    if (r.url().includes('/sanctum/') || r.url().includes('/api/'))
      console.log(`[REQ ${r.method()}] ${r.url()}`);
  });
  page.on('response', async r => {
    if (r.url().includes('/sanctum/') || r.url().includes('/api/')) {
      const body = await r.text();
      console.log(`[RESP ${r.status()}] ${r.url()} => ${body.substring(0,200)}`);
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(3000);

  // Call authStore.login directly from the browser console
  const result = await page.evaluate(async () => {
    try {
      // Find Pinia from the app instance (Vue 3 stores it on the mount element)
      const appEl = document.querySelector('#app');
      const pinia = appEl.__vue_app__.config.globalProperties.$pinia;
      const authStore = pinia._s.get('auth');
      
      if (!authStore) return { error: 'authStore not found', stores: Array.from(pinia._s.keys()) };
      
      const loginResult = await authStore.login({
        email: 'admin@wms.local',
        password: 'password123'
      });
      
      return {
        loginResult,
        isLoggedIn: authStore.isLoggedIn,
        token: authStore.token,
        user: authStore.user ? JSON.stringify(authStore.user).substring(0,100) : null,
        url: window.location.href
      };
    } catch (e) {
      return { error: e.message, stack: e.stack?.substring(0,200) };
    }
  });
  
  console.log('=== DIRECT LOGIN RESULT: ' + JSON.stringify(result, null, 2) + ' ===');
  console.log('=== FINAL URL: ' + page.url() + ' ===');

  // Now try router.push('/')
  const navResult = await page.evaluate(async () => {
    try {
      const appEl = document.querySelector('#app');
      const router = appEl.__vue_app__.config.globalProperties.$router;
      await router.push('/');
      return { url: window.location.href };
    } catch (e) {
      return { error: e.message };
    }
  });
  console.log('=== AFTER ROUTER PUSH: ' + JSON.stringify(navResult) + ' ===');
});
