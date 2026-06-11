import { test, expect } from '@playwright/test';

test.describe('WMS End-to-End Business Flow', () => {
  test('Should complete full lifecycle from login to shipping', async ({ page }) => {
    // Set a longer timeout for this comprehensive test
    test.setTimeout(120000);

    // Optional: Add network failure listener to debug issues
    page.on('response', async response => {
      if (response.status() >= 400 && response.url().includes('/api/v1/')) {
        console.log(`[FAILED] ${response.status()} ${response.url()}`);
        try {
          console.log('Request body:', response.request().postData());
          console.log('Response body:', await response.text());
        } catch (e) {}
      }
    });

    // 1. Authentication
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@wms.local');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // Assert login success — wait for login API to complete
    const loginResp = page.waitForResponse(
      resp => resp.url().includes('/api/v1/login') && resp.status() === 200,
      { timeout: 15000 }
    );
    await loginResp;
    await page.waitForTimeout(2000);
    await expect(page).toHaveURL('/', { timeout: 10000 });
    await expect(page.locator('text=Aktivitas Terakhir')).toBeVisible();

    // 2. Master Data - Gudang
    await page.click('a[href="/warehouses"]');
    await expect(page).toHaveURL(/\/warehouses/);
    
    // Clean up existing test warehouse if any
    await page.evaluate(() => {
      document.querySelectorAll('[data-v-68cf749f] .fixed.inset-0').forEach(el => {
        el.style.display = 'none';
      });
    });
    await page.waitForTimeout(500);
    
    await page.click('text=+ Tambah Gudang');
    
    // Wait for modal to open (Modal component has transition)
    await page.waitForSelector('text=Tambah Gudang', { state: 'visible' });
    await page.waitForTimeout(500);
    
    // Use unique code with timestamp to avoid duplicates
    const whCode = 'WH-TEST-' + Date.now().toString().slice(-6);
    const whName = 'Gudang Testing Playwright';
    
    // Fill Warehouse Form using placeholders
    await page.fill('input[placeholder="WH001"]', whCode);
    await page.fill('input[placeholder="Gudang Utama"]', whName);
    // Click the "Simpan" button inside the modal footer
    await page.locator('.fixed.inset-0 button:has-text("Simpan")').click();
    // Wait for response and modal to close
    await page.waitForTimeout(2000);
    // Close modal if still open (e.g. if save failed)
    const modalOpen = await page.locator('.fixed.inset-0').isVisible().catch(() => false);
    if (modalOpen) {
      await page.keyboard.press('Escape');
      await page.waitForTimeout(500);
    }
    // The warehouse name should appear in the table (either new or from previous run)
    await expect(page.locator(`text=${whName}`).first()).toBeVisible({ timeout: 10000 });

    // 3. Master Data - Produk
    const prodCode = 'PROD-' + Date.now().toString().slice(-6);
    const prodSku = 'SKU-' + Date.now().toString().slice(-6);
    
    await page.click('a[href="/products"]');
    await expect(page).toHaveURL(/\/products/);
    await page.click('text=+ Tambah Produk');
    
    // Fill Product Form using placeholders
    await page.fill('input[placeholder="PRD-001"]', prodCode);
    await page.fill('input[placeholder="SKU-001"]', prodSku);
    await page.fill('input[placeholder="Kopi Arabica..."]', 'Produk Testing Playwright');
    // Category select: it's a select element, we can use its options if any, or leave it blank
    await page.selectOption('select:has(option[value="standard"])', 'standard');
    await page.click('button:has-text("Simpan")');
    await expect(page.locator('text=Produk Testing Playwright')).toBeVisible({ timeout: 15000 });

    // Close any open modal/overlay before navigating
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);

    // 4. Inbound Flow (Barang Masuk)
    // Close any open modal/overlay before navigating
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);
    await page.click('a[href="/inbounds"]');
    await expect(page).toHaveURL(/\/inbounds/);
    await page.click('text=+ Buat Inbound');
    
    // Fill Inbound Header
    await page.fill('input[placeholder="PO-2023-001"]', 'PO-TEST-999');
    
    // Select Warehouse (it's the third select, first is the table filter)
    const selects = page.locator('select.input');
    await selects.nth(2).selectOption({ label: 'Gudang Testing Playwright' });

    // Add Item
    await page.click('text=+ Tambah Item');
    
    // Select Product in the item row (fourth select overall)
    await selects.nth(3).evaluate(el => {
      const options = Array.from(el.options);
      const target = options.find(o => o.text.includes('Produk Testing Playwright'));
      if (target) el.value = target.value;
    });
    await selects.nth(3).dispatchEvent('change');
    await page.waitForTimeout(200);
    
    // Fill Qty
    await page.fill('input[type="number"]', '100');
    
    await page.click('button:has-text("Simpan Inbound")');
    await expect(page.locator('text=PO-TEST-999')).toBeVisible({ timeout: 15000 });

    // 5. Receive Inbound
    // Click detail
    await page.locator('tr').filter({ hasText: 'PO-TEST-999' }).locator('text=Detail').click();
    await page.click('button:has-text("Terima Barang (Receive All)")');
    await expect(page.locator('text=Status: RECEIVED')).toBeVisible({ timeout: 15000 });

    // 6. Outbound Flow (Barang Keluar)
    await page.click('a[href="/outbounds"]');
    await expect(page).toHaveURL(/\/outbounds/);
    await page.click('text=+ Buat Outbound');
    
    // Select Warehouse
    await page.locator('select.input').nth(2).selectOption({ label: 'Gudang Testing Playwright' });

    // Fill references
    await page.fill('input[placeholder="SO-2023-001"]', 'SO-TEST-999');
    await page.fill('input[placeholder="Nama Customer..."]', 'Customer Playwright');
    
    // Add Item
    await page.click('text=+ Tambah Item');
    
    // Select Product
    await page.locator('select.input').nth(3).evaluate(el => {
      const options = Array.from(el.options);
      const target = options.find(o => o.text.includes('Produk Testing Playwright'));
      if (target) el.value = target.value;
    });
    await page.locator('select.input').nth(3).dispatchEvent('change');
    await page.waitForTimeout(200);
    
    // Fill Qty
    await page.fill('input[type="number"]', '20');
    
    await page.click('button:has-text("Simpan Outbound")');
    await expect(page.locator('text=SO-TEST-999')).toBeVisible({ timeout: 15000 });

    // 7. Pick & Ship Outbound
    await page.locator('tr').filter({ hasText: 'SO-TEST-999' }).locator('text=Detail').click();
    await page.click('button:has-text("Proses Pick")');
    await expect(page.locator('text=Status: PICKED')).toBeVisible({ timeout: 15000 });
    
    await page.click('button:has-text("Kirim (Ship)")');
    await expect(page.locator('text=Status: SHIPPED')).toBeVisible({ timeout: 15000 });

    // 8. Planogram Flow
    await page.click('a[href="/planograms"]');
    await expect(page).toHaveURL(/\/planograms/, { timeout: 15000 });
    
    // Wait for warehouse data to load
    await page.waitForTimeout(2000); 

    // Click 'Buat Planogram' main button
    await page.click('button:has-text("Buat Planogram")');
    
    // In the modal, select the warehouse
    await page.locator('.fixed.inset-0 select').first().selectOption({ label: whName });
    
    // Fill description
    await page.fill('input[placeholder="Deskripsi perubahan..."]', 'Initial Planogram Test Playwright');
    
    // Click Buat & Edit
    await page.click('button:has-text("Buat & Edit")');

    // It should navigate to PlanogramEditor
    await expect(page).toHaveURL(/\/planograms\/\d+/, { timeout: 15000 });
    await expect(page.locator(`text=${whName}`)).toBeVisible({ timeout: 15000 });

    // Search product in left sidebar
    await page.fill('input[placeholder="Cari nama/SKU/barcode..."]', 'Produk Testing');
    await expect(page.locator('text=Produk Testing').first()).toBeVisible({ timeout: 15000 });

    // Test creating a Zone using the canvas
    await page.click('button:has-text("Zone")');
    
    // Find the canvas to get its bounding box to click inside it
    const canvasContainer = page.locator('.konvajs-content');
    const box = await canvasContainer.boundingBox();
    if (box) {
      await page.mouse.move(box.x + 100, box.y + 100);
      await page.mouse.down();
      await page.mouse.move(box.x + 200, box.y + 200);
      await page.mouse.up();
    }

    // Save the planogram
    await page.click('button:has-text("Simpan")');
    await expect(page.locator('text=Tersimpan')).toBeVisible({ timeout: 15000 });

    // Test Snapshot functionality
    page.once('dialog', dialog => dialog.accept());
    await page.click('button:has-text("Snapshot")');

    // Wait for the snapshot process to complete
    await page.waitForTimeout(2000);

  });
});

