import { test, expect } from '@playwright/test';

test('Debug actual login error from Vue store', async ({ page }) => {
  test.setTimeout(60000);

  page.on('console', msg => console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`));
  
  // Patch the auth store before clicking submit to capture the error
  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // Patch the Pinia auth store's login function to capture exactly what happens
  await page.evaluate(() => {
    // Wait for Pinia to be available
    setTimeout(() => {
      // Find the pinia instance from the app
      const appEl = document.querySelector('#app');
      if (appEl && appEl.__vue_app__) {
        const pinia = appEl.__vue_app__.config.globalProperties.$pinia;
        if (pinia) {
          const authStore = pinia._s.get('auth');
          if (authStore) {
            const origLogin = authStore.login;
            authStore.login = async function(credentials) {
              try {
                const result = await origLogin.call(this, credentials);
                window.__loginResult = result;
                window.__loginSuccess = result.success;
                return result;
              } catch (e) {
                window.__loginResult = { error: e.message, stack: e.stack?.substring(0, 500) };
                window.__loginSuccess = false;
                throw e;
              }
            };
            window.__piniaFound = true;
          } else {
            window.__piniaFound = false;
            window.__noStore = 'auth';
          }
        } else {
          window.__piniaFound = false;
          window.__noStore = 'pinia';
        }
      } else {
        window.__piniaFound = false;
        window.__noStore = 'app';
        window.__appExists = !!appEl;
        window.__appVue = !!(appEl && appEl.__vue_app__);
      }
    }, 500);
  });

  await page.waitForTimeout(1000);
  
  // Check if we found Pinia
  const piniaFound = await page.evaluate(() => window.__piniaFound);
  console.log('=== PINIA FOUND: ' + piniaFound + ' ===');

  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(5000);

  const result = await page.evaluate(() => ({
    loginResult: window.__loginResult,
    loginSuccess: window.__loginSuccess,
    url: window.location.href,
  }));
  console.log('=== LOGIN RESULT: ' + JSON.stringify(result, null, 2) + ' ===');
  
  // Check for any network errors
  const perfEntries = await page.evaluate(() => 
    performance.getEntriesByType('resource')
      .filter(e => e.name.includes('/api/') || e.name.includes('/sanctum/'))
      .map(e => ({ name: e.name.substring(0, 100), duration: e.duration }))
  );
  console.log('=== NETWORK: ' + JSON.stringify(perfEntries) + ' ===');
});
