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
    await expect(page).toHaveURL('/', { timeout: 15000 });
    await expect(page.locator('text=Aktivitas Terakhir')).toBeVisible();

    // 2. Master Data - Gudang
    await page.click('a[href="/warehouses"]');
    await expect(page).toHaveURL(/\/warehouses/);
    await page.click('text=+ Tambah Gudang');
    
    // Fill Warehouse Form using placeholders
    await page.fill('input[placeholder="WH001"]', 'WH-TEST-001');
    await page.fill('input[placeholder="Gudang Utama"]', 'Gudang Testing Playwright');
    // For selects we might need to target the select element inside the modal
    // But since it's the only form, we can use placeholder or wait for the button
    await page.click('button:has-text("Simpan")');
    await expect(page.locator('text=Gudang Testing Playwright')).toBeVisible({ timeout: 15000 });

    // 3. Master Data - Produk
    await page.click('a[href="/products"]');
    await expect(page).toHaveURL(/\/products/);
    await page.click('text=+ Tambah Produk');
    
    // Fill Product Form using placeholders
    await page.fill('input[placeholder="PRD-001"]', 'PROD-TEST-999');
    await page.fill('input[placeholder="SKU-001"]', 'SKU-TEST-999');
    await page.fill('input[placeholder="Kopi Arabica..."]', 'Produk Testing Playwright');
    // Category select: it's a select element, we can use its options if any, or leave it blank
    await page.click('button:has-text("Simpan")');
    await expect(page.locator('text=Produk Testing Playwright')).toBeVisible({ timeout: 15000 });

    // 4. Inbound Flow (Barang Masuk)
    await page.click('a[href="/inbounds"]');
    await expect(page).toHaveURL(/\/inbounds/);
    await page.click('text=+ Buat Inbound');
    
    // Fill Inbound Header
    await page.fill('input[placeholder="PO-2023-001"]', 'PO-TEST-999');
    
    // Select Warehouse (it's the second select)
    const selects = page.locator('.modal-body select, select.input');
    await selects.nth(1).selectOption({ label: 'Gudang Testing Playwright' });

    // Add Item
    await page.click('text=+ Tambah Item');
    
    // Select Product in the item row (third select overall)
    await selects.nth(2).selectOption({ label: 'SKU-TEST-999 - Produk Testing Playwright' });
    
    // Fill Qty
    await page.fill('input[type="number"]', '100');
    
    await page.click('button:has-text("Simpan Inbound")');
    await expect(page.locator('text=PO-TEST-999')).toBeVisible({ timeout: 15000 });

    // 5. Receive Inbound
    // Click detail
    await page.click('text=PO-TEST-999');
    await page.click('button:has-text("Terima Semua (Auto)")');
    await expect(page.locator('text=Status: RECEIVED')).toBeVisible({ timeout: 15000 });

    // 6. Outbound Flow (Barang Keluar)
    await page.click('a[href="/outbounds"]');
    await expect(page).toHaveURL(/\/outbounds/);
    await page.click('text=+ Buat Outbound');
    
    // Select Warehouse
    await page.locator('select.input').nth(1).selectOption({ label: 'Gudang Testing Playwright' });

    // Fill references
    await page.fill('input[placeholder="SO-2023-001"]', 'SO-TEST-999');
    await page.fill('input[placeholder="Nama Customer..."]', 'Customer Playwright');
    
    // Add Item
    await page.click('text=+ Tambah Item');
    
    // Select Product
    await page.locator('select.input').nth(2).selectOption({ label: 'SKU-TEST-999 - Produk Testing Playwright' });
    
    // Fill Qty
    await page.fill('input[type="number"]', '20');
    
    await page.click('button:has-text("Simpan Outbound")');
    await expect(page.locator('text=SO-TEST-999')).toBeVisible({ timeout: 15000 });

    // 7. Pick & Ship Outbound
    await page.click('text=SO-TEST-999');
    await page.click('button:has-text("Pick Semua (Auto)")');
    await expect(page.locator('text=Status: PICKED')).toBeVisible({ timeout: 15000 });
    
    await page.click('button:has-text("Kirim Pesanan")');
    await expect(page.locator('text=Status: SHIPPED')).toBeVisible({ timeout: 15000 });

  });
});
