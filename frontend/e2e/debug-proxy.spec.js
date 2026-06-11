import { test, expect } from '@playwright/test';

test('Debug CSRF and login via Vite proxy (same-origin)', async ({ page }) => {
  test.setTimeout(60000);

  page.on('console', msg => {
    console.log(`[CONSOLE ${msg.type()}] ${msg.text()}`);
  });
  page.on('request', request => {
    const url = request.url();
    if (url.includes('/sanctum/') || url.includes('/api/')) {
      console.log(`[REQ ${request.method()}] ${url}`);
    }
  });
  page.on('requestfailed', request => {
    console.log(`[REQ FAILED] ${request.url()} - ${request.failure()?.errorText}`);
  });
  page.on('response', async response => {
    const url = response.url();
    if (url.includes('/sanctum/') || url.includes('/api/')) {
      try {
        const body = await response.text();
        console.log(`[RESP ${response.status()}] ${url} Body: ${body.substring(0, 200)}`);
      } catch(e) {
        console.log(`[RESP ${response.status()}] ${url} (no body)`);
      }
    }
  });

  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(2000);

  // CSRF via same-origin
  const csrfResult = await page.evaluate(async () => {
    try {
      const resp = await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      });
      return { status: resp.status, ok: resp.ok, cookies: document.cookie };
    } catch (e) {
      return { error: e.message, name: e.name };
    }
  });
  console.log('=== CSRF === ' + JSON.stringify(csrfResult));

  // Login via same-origin with XSRF token from cookie
  const loginResult = await page.evaluate(async () => {
    const getCookie = (name) => {
      const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
      return match ? decodeURIComponent(match[2]) : '';
    };
    const token = getCookie('XSRF-TOKEN');
    try {
      const resp = await fetch('/api/v1/login', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-XSRF-TOKEN': token || ''
        },
        body: JSON.stringify({ email: 'admin@wms.local', password: 'password123' })
      });
      const text = await resp.text();
      return { status: resp.status, ok: resp.ok, body: text.substring(0, 300) };
    } catch (e) {
      return { error: e.message, name: e.name };
    }
  });
  console.log('=== LOGIN === ' + JSON.stringify(loginResult));
  console.log('=== FINAL URL: ' + page.url() + ' ===');
});
