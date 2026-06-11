import { test, expect } from '@playwright/test';

test('debug login with network failures', async ({ page }) => {
  test.setTimeout(60000);

  page.on('console', msg => {
    console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });

  page.on('requestfailed', request => {
    console.log(`[FAILED REQ] ${request.url()} - ${request.failure()?.errorText || 'unknown'}`);
  });

  page.on('request', request => {
    const url = request.url();
    if (url.includes('/api/') || url.includes('/sanctum/')) {
      console.log(`[REQ ${request.method()}] ${url}`);
    }
  });

  page.on('response', async response => {
    const url = response.url();
    if (url.includes('/api/') || url.includes('/sanctum/')) {
      console.log(`[RESP ${response.status()}] ${url}`);
      if (response.status() >= 400) {
        try { console.log('  Body:', await response.text()); } catch(e) {}
      }
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // Manual CSRF fetch from browser context
  const csrfResult = await page.evaluate(async () => {
    try {
      const resp = await fetch('http://localhost:8000/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json, text/plain, */*' }
      });
      return { 
        status: resp.status, 
        ok: resp.ok, 
        cookies: document.cookie,
        headers: Object.fromEntries(resp.headers.entries())
      };
    } catch (e) {
      return { error: e.message, name: e.name, stack: e.stack };
    }
  });
  console.log('=== CSRF FETCH RESULT ===');
  console.log(JSON.stringify(csrfResult, null, 2));

  await page.waitForTimeout(1000);

  // Now try login via browser fetch
  const loginResult = await page.evaluate(async () => {
    try {
      const resp = await fetch('http://localhost:8000/api/v1/login', {
        method: 'POST',
        credentials: 'include',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-XSRF-TOKEN': 'dummy'
        },
        body: JSON.stringify({ email: 'admin@wms.local', password: 'password123' })
      });
      const text = await resp.text();
      return { status: resp.status, ok: resp.ok, body: text };
    } catch (e) {
      return { error: e.message, name: e.name, stack: e.stack };
    }
  });
  console.log('=== LOGIN RESULT ===');
  console.log(JSON.stringify(loginResult, null, 2));

  console.log('=== FINAL URL: ' + page.url() + ' ===');
});
