import { test, expect } from '@playwright/test';

test('debug login POST only', async ({ page }) => {
  test.setTimeout(30000);

  // Just test login POST
  const result = await page.evaluate(async () => {
    try {
      const resp = await fetch('http://localhost:8000/api/v1/login', {
        method: 'POST',
        credentials: 'include',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-XSRF-TOKEN': 'test'
        },
        body: JSON.stringify({ email: 'admin@wms.local', password: 'password123' })
      });
      const text = await resp.text();
      return { status: resp.status, body: text.substring(0, 300) };
    } catch (e) {
      return { error: e.message, name: e.name, stack: e.stack?.substring(0, 300) };
    }
  });
  console.log('=== LOGIN POST ===');
  console.log(JSON.stringify(result, null, 2));
});
