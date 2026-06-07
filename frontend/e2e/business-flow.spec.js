import { test, expect } from '@playwright/test';

test.describe('WMS End-to-End Business Flow', () => {

  test('Should complete full lifecycle from login to shipping', async ({ page }) => {
    // Set a longer timeout for this comprehensive test
    test.setTimeout(120000);

    // 1. Authentication
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@wms.local');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // Assert login success
    await expect(page).toHaveURL('/');
    await expect(page.locator('text=Pusat Laporan')).toBeVisible();

    // 2. Master Data - Gudang
    await page.click('a[href="/warehouses"]');
    await expect(page).toHaveURL(/\/warehouses/);
    await page.click('text=Tambah Gudang');
    
    // Fill Gudang Form
    await page.fill('input[id="code"]', 'WH-TEST-01');
    await page.fill('input[id="name"]', 'Gudang Testing Utama');
    await page.fill('input[id="capacity_m2"]', '5000');
    await page.selectOption('select[id="warehouse_type"]', 'reguler');
    await page.click('button[type="submit"]');
    
    // Assert Success Toast
    await expect(page.locator('text=berhasil')).toBeVisible();

    // 3. Master Data - Produk
    await page.click('a[href="/products"]');
    await expect(page).toHaveURL(/\/products/);
    await page.click('text=Tambah Produk');
    
    // Fill Produk Form
    await page.fill('input[id="sku"]', 'PRD-TEST-001');
    await page.fill('input[id="name"]', 'Produk Testing Playwright');
    await page.fill('input[id="price"]', '150000');
    await page.click('button[type="submit"]');
    
    // Assert Success Toast
    await expect(page.locator('text=berhasil')).toBeVisible();

    // 4. Inbound Flow (Barang Masuk)
    await page.click('a[href="/inbounds"]');
    await expect(page).toHaveURL(/\/inbounds/);
    await page.click('text=Inbound Baru');
    
    // Fill Inbound Header
    await page.fill('input[id="reference_number"]', 'PO-TEST-999');
    // Select Warehouse
    await page.selectOption('select[id="warehouse_id"]', { label: 'Gudang Testing Utama' });
    await page.click('button[type="submit"]'); // Save draft

    // In the detail page, add an item
    await page.click('text=Tambah Item');
    await page.selectOption('select[id="product_id"]', { label: 'Produk Testing Playwright' });
    await page.fill('input[id="expected_qty"]', '100');
    await page.click('button[type="submit"]');

    // Click Receive
    await page.click('button:has-text("Receive")');
    await expect(page.locator('text=berhasil')).toBeVisible();

    // 5. Planogram & Utilisasi
    await page.click('a[href="/reports"]');
    await expect(page).toHaveURL(/\/reports/);
    await page.click('text=Utilisasi Gudang');
    // Just verify the page doesn't crash and shows "Total Slot"
    await expect(page.locator('text=Total Slot').first()).toBeVisible();

    // 6. Outbound Flow (Barang Keluar)
    await page.click('a[href="/outbounds"]');
    await expect(page).toHaveURL(/\/outbounds/);
    await page.click('text=Outbound Baru');
    
    await page.fill('input[id="reference_number"]', 'SO-TEST-777');
    await page.selectOption('select[id="warehouse_id"]', { label: 'Gudang Testing Utama' });
    await page.click('button[type="submit"]');

    // Add item to outbound
    await page.click('text=Tambah Item');
    await page.selectOption('select[id="product_id"]', { label: 'Produk Testing Playwright' });
    await page.fill('input[id="ordered_qty"]', '10');
    await page.click('button[type="submit"]');

    // Process Outbound: Pick then Ship
    await page.click('button:has-text("Pick")');
    await expect(page.locator('text=berhasil')).toBeVisible();
    
    await page.click('button:has-text("Ship")');
    await expect(page.locator('text=berhasil')).toBeVisible();

    // End of E2E Flow
  });

});
