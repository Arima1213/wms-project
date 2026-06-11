import { test, expect } from '@playwright/test';

test('Debug warehouse creation', async ({ page }) => {
  test.setTimeout(60000);

  page.on('console', msg => {
    if (msg.type() === 'error') console.log(`[CONSOLE ERROR] ${msg.text()}`);
  });
  page.on('response', async r => {
    if (r.url().includes('/api/v1/warehouses')) {
      const body = await r.text();
      console.log(`[API ${r.status()}] ${r.url().substring(0,100)} => ${body.substring(0,200)}`);
    }
  });
  page.on('request', r => {
    if (r.url().includes('/api/v1/warehouses'))
      console.log(`[REQ ${r.method()}] ${r.url().substring(0,100)}`);
  });

  // Login first
  await page.goto('http://localhost:5173/login');
  await page.waitForTimeout(1000);
  await page.fill('input[type="email"]', 'admin@wms.local');
  await page.fill('input[type="password"]', 'password123');

  const loginResp = page.waitForResponse(r => r.url().includes('/api/v1/login') && r.status() === 200);
  await page.click('button[type="submit"]');
  await loginResp;
  await page.waitForTimeout(2000);

  console.log('=== LOGGED IN, URL: ' + page.url() + ' ===');

  // Navigate to warehouses
  await page.click('a[href="/warehouses"]');
  await page.waitForTimeout(2000);
  console.log('=== ON WAREHOUSES, URL: ' + page.url() + ' ===');

  // Click + Tambah Gudang
  await page.click('text=+ Tambah Gudang');
  await page.waitForTimeout(1000);

  // Check what's visible now
  const modalVisible = await page.locator('.fixed.inset-0').isVisible().catch(() => false);
  console.log('=== MODAL VISIBLE: ' + modalVisible + ' ===');

  // Check the inputs
  const codeInput = page.locator('input[placeholder="WH001"]');
  const nameInput = page.locator('input[placeholder="Gudang Utama"]');
  console.log('=== CODE INPUT EXISTS: ' + (await codeInput.isVisible().catch(() => false)) + ' ===');
  console.log('=== NAME INPUT EXISTS: ' + (await nameInput.isVisible().catch(() => false)) + ' ===');

  // Fill
  await codeInput.fill('WH-TEST-001');
  await nameInput.fill('Gudang Testing Playwright');
  await page.waitForTimeout(500);

  // Check save button
  const saveBtn = page.locator('.fixed.inset-0 button:has-text("Simpan")');
  console.log('=== SAVE BTN VISIBLE: ' + (await saveBtn.isVisible().catch(() => false)) + ' ===');
  console.log('=== SAVE BTN ENABLED: ' + (await saveBtn.isEnabled().catch(() => false)) + ' ===');

  // Click save and wait for API response
  const warehouseResp = page.waitForResponse(
    r => r.url().includes('/api/v1/warehouses') && r.request().method() === 'POST',
    { timeout: 10000 }
  );
  await saveBtn.click();
  
  try {
    const resp = await warehouseResp;
    console.log('=== WAREHOUSE API: ' + resp.status() + ' ===');
    console.log('=== WAREHOUSE BODY: ' + (await resp.text()).substring(0,300) + ' ===');
  } catch (e) {
    console.log('=== NO API RESPONSE: ' + e.message + ' ===');
  }

  await page.waitForTimeout(2000);
  console.log('=== FINAL URL: ' + page.url() + ' ===');
  
  // Check if warehouse appears
  const textFound = await page.locator('text=Gudang Testing Playwright').isVisible().catch(() => false);
  console.log('=== WAREHOUSE VISIBLE IN TABLE: ' + textFound + ' ===');
});
