# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: business-flow.spec.js >> WMS End-to-End Business Flow >> Should complete full lifecycle from login to shipping
- Location: e2e\business-flow.spec.js:5:3

# Error details

```
Error: expect(page).toHaveURL(expected) failed

Expected: "http://localhost:5173/"
Received: "http://localhost:5173/login"
Timeout:  5000ms

Call log:
  - Expect "toHaveURL" with timeout 5000ms
    14 × unexpected value "http://localhost:5173/login"

```

```yaml
- heading "WMS" [level=1]
- paragraph: Multi-Gudang Management System
- heading "Login" [level=2]
- text: Email
- textbox "admin@wms.local"
- text: Password
- textbox "••••••••": password123
- button "Logging in..." [disabled]
- paragraph: "Demo: admin@wms.local / password123"
```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test';
  2   | 
  3   | test.describe('WMS End-to-End Business Flow', () => {
  4   | 
  5   |   test('Should complete full lifecycle from login to shipping', async ({ page }) => {
  6   |     // Set a longer timeout for this comprehensive test
  7   |     test.setTimeout(120000);
  8   | 
  9   |     // 1. Authentication
  10  |     await page.goto('/login');
  11  |     await page.fill('input[type="email"]', 'admin@wms.local');
  12  |     await page.fill('input[type="password"]', 'password123');
  13  |     await page.click('button[type="submit"]');
  14  |     
  15  |     // Assert login success
> 16  |     await expect(page).toHaveURL('/');
      |                        ^ Error: expect(page).toHaveURL(expected) failed
  17  |     await expect(page.locator('text=Pusat Laporan')).toBeVisible();
  18  | 
  19  |     // 2. Master Data - Gudang
  20  |     await page.click('a[href="/warehouses"]');
  21  |     await expect(page).toHaveURL(/\/warehouses/);
  22  |     await page.click('text=Tambah Gudang');
  23  |     
  24  |     // Fill Gudang Form
  25  |     await page.fill('input[id="code"]', 'WH-TEST-01');
  26  |     await page.fill('input[id="name"]', 'Gudang Testing Utama');
  27  |     await page.fill('input[id="capacity_m2"]', '5000');
  28  |     await page.selectOption('select[id="warehouse_type"]', 'reguler');
  29  |     await page.click('button[type="submit"]');
  30  |     
  31  |     // Assert Success Toast
  32  |     await expect(page.locator('text=berhasil')).toBeVisible();
  33  | 
  34  |     // 3. Master Data - Produk
  35  |     await page.click('a[href="/products"]');
  36  |     await expect(page).toHaveURL(/\/products/);
  37  |     await page.click('text=Tambah Produk');
  38  |     
  39  |     // Fill Produk Form
  40  |     await page.fill('input[id="sku"]', 'PRD-TEST-001');
  41  |     await page.fill('input[id="name"]', 'Produk Testing Playwright');
  42  |     await page.fill('input[id="price"]', '150000');
  43  |     await page.click('button[type="submit"]');
  44  |     
  45  |     // Assert Success Toast
  46  |     await expect(page.locator('text=berhasil')).toBeVisible();
  47  | 
  48  |     // 4. Inbound Flow (Barang Masuk)
  49  |     await page.click('a[href="/inbounds"]');
  50  |     await expect(page).toHaveURL(/\/inbounds/);
  51  |     await page.click('text=Inbound Baru');
  52  |     
  53  |     // Fill Inbound Header
  54  |     await page.fill('input[id="reference_number"]', 'PO-TEST-999');
  55  |     // Select Warehouse
  56  |     await page.selectOption('select[id="warehouse_id"]', { label: 'Gudang Testing Utama' });
  57  |     await page.click('button[type="submit"]'); // Save draft
  58  | 
  59  |     // In the detail page, add an item
  60  |     await page.click('text=Tambah Item');
  61  |     await page.selectOption('select[id="product_id"]', { label: 'Produk Testing Playwright' });
  62  |     await page.fill('input[id="expected_qty"]', '100');
  63  |     await page.click('button[type="submit"]');
  64  | 
  65  |     // Click Receive
  66  |     await page.click('button:has-text("Receive")');
  67  |     await expect(page.locator('text=berhasil')).toBeVisible();
  68  | 
  69  |     // 5. Planogram & Utilisasi
  70  |     await page.click('a[href="/reports"]');
  71  |     await expect(page).toHaveURL(/\/reports/);
  72  |     await page.click('text=Utilisasi Gudang');
  73  |     // Just verify the page doesn't crash and shows "Total Slot"
  74  |     await expect(page.locator('text=Total Slot').first()).toBeVisible();
  75  | 
  76  |     // 6. Outbound Flow (Barang Keluar)
  77  |     await page.click('a[href="/outbounds"]');
  78  |     await expect(page).toHaveURL(/\/outbounds/);
  79  |     await page.click('text=Outbound Baru');
  80  |     
  81  |     await page.fill('input[id="reference_number"]', 'SO-TEST-777');
  82  |     await page.selectOption('select[id="warehouse_id"]', { label: 'Gudang Testing Utama' });
  83  |     await page.click('button[type="submit"]');
  84  | 
  85  |     // Add item to outbound
  86  |     await page.click('text=Tambah Item');
  87  |     await page.selectOption('select[id="product_id"]', { label: 'Produk Testing Playwright' });
  88  |     await page.fill('input[id="ordered_qty"]', '10');
  89  |     await page.click('button[type="submit"]');
  90  | 
  91  |     // Process Outbound: Pick then Ship
  92  |     await page.click('button:has-text("Pick")');
  93  |     await expect(page.locator('text=berhasil')).toBeVisible();
  94  |     
  95  |     await page.click('button:has-text("Ship")');
  96  |     await expect(page.locator('text=berhasil')).toBeVisible();
  97  | 
  98  |     // End of E2E Flow
  99  |   });
  100 | 
  101 | });
  102 | 
```