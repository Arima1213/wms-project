# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: business-flow.spec.js >> WMS End-to-End Business Flow >> Should complete full lifecycle from login to shipping
- Location: e2e\business-flow.spec.js:4:3

# Error details

```
Test timeout of 120000ms exceeded.
```

```
Error: locator.selectOption: Test timeout of 120000ms exceeded.
Call log:
  - waiting for locator('select.input').nth(3)
    - locator resolved to <select class="input input-sm w-full">…</select>
  - attempting select option action
    2 × waiting for element to be visible and enabled
      - did not find some options
    - retrying select option action
    - waiting 20ms
    2 × waiting for element to be visible and enabled
      - did not find some options
    - retrying select option action
      - waiting 100ms
    183 × waiting for element to be visible and enabled
        - did not find some options
      - retrying select option action
        - waiting 500ms

```

# Page snapshot

```yaml
- generic [ref=e1]:
  - generic [ref=e3]:
    - complementary [ref=e4]:
      - generic [ref=e5]:
        - heading "WMS" [level=1] [ref=e6]
        - paragraph [ref=e7]: Multi-Gudang System
      - navigation [ref=e8]:
        - link "Dashboard" [ref=e9] [cursor=pointer]:
          - /url: /
          - img [ref=e10]
          - text: Dashboard
        - link "Gudang" [ref=e12] [cursor=pointer]:
          - /url: /warehouses
          - img [ref=e13]
          - text: Gudang
        - link "Kategori" [ref=e15] [cursor=pointer]:
          - /url: /categories
          - img [ref=e16]
          - text: Kategori
        - link "Produk" [ref=e19] [cursor=pointer]:
          - /url: /products
          - img [ref=e20]
          - text: Produk
        - link "Barang Masuk" [ref=e22] [cursor=pointer]:
          - /url: /inbounds
          - img [ref=e23]
          - text: Barang Masuk
        - link "Barang Keluar" [ref=e25] [cursor=pointer]:
          - /url: /outbounds
          - img [ref=e26]
          - text: Barang Keluar
        - link "Stok" [ref=e28] [cursor=pointer]:
          - /url: /stock
          - img [ref=e29]
          - text: Stok
        - link "Stock Opname" [ref=e31] [cursor=pointer]:
          - /url: /stock-opnames
          - img [ref=e32]
          - text: Stock Opname
        - link "Transfer Stok" [ref=e34] [cursor=pointer]:
          - /url: /transfers
          - img [ref=e35]
          - text: Transfer Stok
        - link "Planogram" [ref=e37] [cursor=pointer]:
          - /url: /planograms
          - img [ref=e38]
          - text: Planogram
        - link "Laporan" [ref=e40] [cursor=pointer]:
          - /url: /reports
          - img [ref=e41]
          - text: Laporan
        - link "Notifikasi" [ref=e43] [cursor=pointer]:
          - /url: /notifications
          - img [ref=e44]
          - text: Notifikasi
        - link "Zona" [ref=e46] [cursor=pointer]:
          - /url: /zones
          - img [ref=e47]
          - text: Zona
        - link "Rak & Slot" [ref=e50] [cursor=pointer]:
          - /url: /racks
          - img [ref=e51]
          - text: Rak & Slot
        - link "Dokumen" [ref=e53] [cursor=pointer]:
          - /url: /documents
          - img [ref=e54]
          - text: Dokumen
        - link "Audit Log" [ref=e56] [cursor=pointer]:
          - /url: /audit-logs
          - img [ref=e57]
          - text: Audit Log
        - link "Pengaturan" [ref=e59] [cursor=pointer]:
          - /url: /settings
          - img [ref=e60]
          - text: Pengaturan
      - generic [ref=e63]:
        - generic [ref=e64]:
          - generic [ref=e65]: A
          - generic [ref=e66]:
            - paragraph [ref=e67]: Administrator
            - paragraph [ref=e68]: admin@wms.local
        - button "Logout" [ref=e69] [cursor=pointer]: Logout
    - main [ref=e71]:
      - generic [ref=e72]:
        - heading "Inbounds" [level=2] [ref=e73]
        - generic [ref=e74]:
          - button [ref=e76] [cursor=pointer]:
            - img [ref=e77]
          - generic [ref=e79]: Kamis, 11 Juni 2026
      - generic [ref=e81]:
        - generic [ref=e82]:
          - generic [ref=e83]:
            - heading "Inbound (Penerimaan)" [level=2] [ref=e84]
            - generic [ref=e85]:
              - link "Dashboard" [ref=e86] [cursor=pointer]:
                - /url: /
              - img [ref=e87]
              - generic [ref=e89]: Inbound
          - button "+ Buat Inbound" [ref=e90] [cursor=pointer]
        - generic [ref=e91]:
          - generic [ref=e93]:
            - generic [ref=e94]:
              - img [ref=e95]
              - textbox "Cari referensi atau kode..." [ref=e97]
            - combobox [ref=e98]:
              - option "Semua Status" [selected]
              - option "Pending"
              - option "Received"
              - option "Cancelled"
          - table [ref=e100]:
            - rowgroup [ref=e101]:
              - row "No. Inbound Tipe Ref. Sumber Tgl. Harap Status Aksi" [ref=e102]:
                - columnheader "No. Inbound" [ref=e103]:
                  - generic [ref=e104]: No. Inbound
                - columnheader "Tipe" [ref=e105]:
                  - generic [ref=e106]: Tipe
                - columnheader "Ref. Sumber" [ref=e107]:
                  - generic [ref=e108]: Ref. Sumber
                - columnheader "Tgl. Harap" [ref=e109]:
                  - generic [ref=e110]: Tgl. Harap
                - columnheader "Status" [ref=e111]:
                  - generic [ref=e112]: Status
                - columnheader "Aksi" [ref=e113]:
                  - generic [ref=e114]: Aksi
            - rowgroup [ref=e115]:
              - row "Tidak ada data Data belum tersedia." [ref=e116]:
                - cell "Tidak ada data Data belum tersedia." [ref=e117]:
                  - generic [ref=e118]:
                    - img [ref=e119]
                    - heading "Tidak ada data" [level=3] [ref=e121]
                    - paragraph [ref=e122]: Data belum tersedia.
          - generic [ref=e124]:
            - generic [ref=e125]: 0–0 dari 0
            - generic [ref=e126]:
              - button [disabled] [ref=e127]:
                - img [ref=e128]
              - button [disabled] [ref=e130]:
                - img [ref=e131]
              - button "1" [ref=e133] [cursor=pointer]
              - button [disabled] [ref=e134]:
                - img [ref=e135]
              - button [disabled] [ref=e137]:
                - img [ref=e138]
  - generic [ref=e142]:
    - generic [ref=e143]:
      - heading "Buat Inbound Baru" [level=3] [ref=e144]
      - button [ref=e145] [cursor=pointer]:
        - img [ref=e146]
    - generic [ref=e149]:
      - generic [ref=e150]:
        - generic [ref=e151]:
          - generic [ref=e152]: Tipe Transaksi *
          - combobox [ref=e153]:
            - option "Purchase Order (PO)" [selected]
            - option "Return Customer"
            - option "Transfer In"
            - option "Lainnya"
        - generic [ref=e154]:
          - generic [ref=e155]: Gudang Tujuan *
          - combobox [ref=e156]:
            - option "-- Pilih Gudang --"
            - option "Gudang Cold Storage Bekasi"
            - option "Gudang Distribusi Surabaya"
            - option "Gudang Testing Playwright" [selected]
            - option "Gudang Testing Playwright"
            - option "Gudang Testing Playwright"
            - option "Gudang Testing Playwright"
            - option "Gudang Testing Playwright"
            - option "Gudang Utama Jakarta"
        - generic [ref=e157]:
          - generic [ref=e158]: Nomor Referensi Sumber
          - textbox "PO-2023-001" [ref=e159]: PO-TEST-999
        - generic [ref=e160]:
          - generic [ref=e161]: Tanggal Diharapkan
          - textbox [ref=e162]
        - generic [ref=e163]:
          - generic [ref=e164]: Catatan
          - textbox "Catatan opsional..." [ref=e165]
      - generic [ref=e166]:
        - generic [ref=e167]:
          - heading "Daftar Item Produk" [level=4] [ref=e168]
          - button "+ Tambah Item" [active] [ref=e169] [cursor=pointer]
        - generic [ref=e171]:
          - generic [ref=e172]:
            - generic [ref=e173]: Produk
            - combobox [ref=e174]:
              - option "-- Pilih --" [selected]
              - option "ELEC-002 - Adapter Listrik 5V 2A"
              - option "FMCG-003 - Air Mineral 600ml"
              - option "FOOD-001 - Beras Premium 5kg"
              - option "PACK-002 - Bubble Wrap Roll 50M"
              - option "CHEM-001 - Desinfektan 5L"
              - option "ELEC-001 - Kabel USB Type-C 1M"
              - option "FMCG-004 - Kecap Manis 135ml"
              - option "ELEC-004 - Keyboard Mechanical RGB"
              - option "PACK-001 - Kotak Karton 30x30x30"
              - option "PACK-004 - Label Stiker 100x150mm"
              - option "FMCG-002 - Mie Instan Goreng"
              - option "FOOD-002 - Minyak Goreng 2L"
              - option "ELEC-003 - Mouse Wireless Bluetooth"
              - option "CHEM-002 - Pelarut Industri A-100"
              - option "RAWM-001 - Plastik PE 0.5mm"
              - option "SKU-443674 - Produk Testing Playwright"
              - option "SKU-TEST-999 - Produk Testing Playwright"
              - option "RAWM-002 - Resin PP Hitam"
              - option "PACK-003 - Stretch Film 500M"
              - option "FMCG-001 - Susu UHT Full Cream 1L"
          - generic [ref=e175]:
            - generic [ref=e176]: Qty
            - spinbutton [ref=e177]: "1"
          - generic [ref=e178]:
            - generic [ref=e179]: Batch/Lot
            - textbox "LOT-..." [ref=e180]
          - generic [ref=e181]:
            - generic [ref=e182]: Expiry
            - textbox [ref=e183]
          - button [ref=e185] [cursor=pointer]:
            - img [ref=e186]
    - generic [ref=e189]:
      - button "Batal" [ref=e190] [cursor=pointer]
      - button "Simpan Inbound" [ref=e191] [cursor=pointer]
```

# Test source

```ts
  17  |     });
  18  | 
  19  |     // 1. Authentication
  20  |     await page.goto('/login');
  21  |     await page.fill('input[type="email"]', 'admin@wms.local');
  22  |     await page.fill('input[type="password"]', 'password123');
  23  |     await page.click('button[type="submit"]');
  24  |     
  25  |     // Assert login success — wait for login API to complete
  26  |     const loginResp = page.waitForResponse(
  27  |       resp => resp.url().includes('/api/v1/login') && resp.status() === 200,
  28  |       { timeout: 15000 }
  29  |     );
  30  |     await loginResp;
  31  |     await page.waitForTimeout(2000);
  32  |     await expect(page).toHaveURL('/', { timeout: 10000 });
  33  |     await expect(page.locator('text=Aktivitas Terakhir')).toBeVisible();
  34  | 
  35  |     // 2. Master Data - Gudang
  36  |     await page.click('a[href="/warehouses"]');
  37  |     await expect(page).toHaveURL(/\/warehouses/);
  38  |     
  39  |     // Clean up existing test warehouse if any
  40  |     await page.evaluate(() => {
  41  |       document.querySelectorAll('[data-v-68cf749f] .fixed.inset-0').forEach(el => {
  42  |         el.style.display = 'none';
  43  |       });
  44  |     });
  45  |     await page.waitForTimeout(500);
  46  |     
  47  |     await page.click('text=+ Tambah Gudang');
  48  |     
  49  |     // Wait for modal to open (Modal component has transition)
  50  |     await page.waitForSelector('text=Tambah Gudang', { state: 'visible' });
  51  |     await page.waitForTimeout(500);
  52  |     
  53  |     // Use unique code with timestamp to avoid duplicates
  54  |     const whCode = 'WH-TEST-' + Date.now().toString().slice(-6);
  55  |     const whName = 'Gudang Testing Playwright';
  56  |     
  57  |     // Fill Warehouse Form using placeholders
  58  |     await page.fill('input[placeholder="WH001"]', whCode);
  59  |     await page.fill('input[placeholder="Gudang Utama"]', whName);
  60  |     // Click the "Simpan" button inside the modal footer
  61  |     await page.locator('.fixed.inset-0 button:has-text("Simpan")').click();
  62  |     // Wait for response and modal to close
  63  |     await page.waitForTimeout(2000);
  64  |     // Close modal if still open (e.g. if save failed)
  65  |     const modalOpen = await page.locator('.fixed.inset-0').isVisible().catch(() => false);
  66  |     if (modalOpen) {
  67  |       await page.keyboard.press('Escape');
  68  |       await page.waitForTimeout(500);
  69  |     }
  70  |     // The warehouse name should appear in the table (either new or from previous run)
  71  |     await expect(page.locator(`text=${whName}`).first()).toBeVisible({ timeout: 10000 });
  72  | 
  73  |     // 3. Master Data - Produk
  74  |     const prodCode = 'PROD-' + Date.now().toString().slice(-6);
  75  |     const prodSku = 'SKU-' + Date.now().toString().slice(-6);
  76  |     
  77  |     await page.click('a[href="/products"]');
  78  |     await expect(page).toHaveURL(/\/products/);
  79  |     await page.click('text=+ Tambah Produk');
  80  |     
  81  |     // Fill Product Form using placeholders
  82  |     await page.fill('input[placeholder="PRD-001"]', prodCode);
  83  |     await page.fill('input[placeholder="SKU-001"]', prodSku);
  84  |     await page.fill('input[placeholder="Kopi Arabica..."]', 'Produk Testing Playwright');
  85  |     // Category select: it's a select element, we can use its options if any, or leave it blank
  86  |     await page.selectOption('select:has(option[value="standard"])', 'standard');
  87  |     await page.click('button:has-text("Simpan")');
  88  |     await expect(page.locator('text=Produk Testing Playwright')).toBeVisible({ timeout: 15000 });
  89  | 
  90  |     // Close any open modal/overlay before navigating
  91  |     await page.keyboard.press('Escape');
  92  |     await page.waitForTimeout(500);
  93  |     await page.keyboard.press('Escape');
  94  |     await page.waitForTimeout(300);
  95  | 
  96  |     // 4. Inbound Flow (Barang Masuk)
  97  |     // Close any open modal/overlay before navigating
  98  |     await page.keyboard.press('Escape');
  99  |     await page.waitForTimeout(500);
  100 |     await page.keyboard.press('Escape');
  101 |     await page.waitForTimeout(300);
  102 |     await page.click('a[href="/inbounds"]');
  103 |     await expect(page).toHaveURL(/\/inbounds/);
  104 |     await page.click('text=+ Buat Inbound');
  105 |     
  106 |     // Fill Inbound Header
  107 |     await page.fill('input[placeholder="PO-2023-001"]', 'PO-TEST-999');
  108 |     
  109 |     // Select Warehouse (it's the third select, first is the table filter)
  110 |     const selects = page.locator('select.input');
  111 |     await selects.nth(2).selectOption({ label: 'Gudang Testing Playwright' });
  112 | 
  113 |     // Add Item
  114 |     await page.click('text=+ Tambah Item');
  115 |     
  116 |     // Select Product in the item row (fourth select overall)
> 117 |     await selects.nth(3).selectOption({ label: 'Produk Testing Playwright' });
      |                          ^ Error: locator.selectOption: Test timeout of 120000ms exceeded.
  118 |     
  119 |     // Fill Qty
  120 |     await page.fill('input[type="number"]', '100');
  121 |     
  122 |     await page.click('button:has-text("Simpan Inbound")');
  123 |     await expect(page.locator('text=PO-TEST-999')).toBeVisible({ timeout: 15000 });
  124 | 
  125 |     // 5. Receive Inbound
  126 |     // Click detail
  127 |     await page.locator('tr').filter({ hasText: 'PO-TEST-999' }).locator('text=Detail').click();
  128 |     await page.click('button:has-text("Terima Barang (Receive All)")');
  129 |     await expect(page.locator('text=Status: RECEIVED')).toBeVisible({ timeout: 15000 });
  130 | 
  131 |     // 6. Outbound Flow (Barang Keluar)
  132 |     await page.click('a[href="/outbounds"]');
  133 |     await expect(page).toHaveURL(/\/outbounds/);
  134 |     await page.click('text=+ Buat Outbound');
  135 |     
  136 |     // Select Warehouse
  137 |     await page.locator('select.input').nth(2).selectOption({ label: 'Gudang Testing Playwright' });
  138 | 
  139 |     // Fill references
  140 |     await page.fill('input[placeholder="SO-2023-001"]', 'SO-TEST-999');
  141 |     await page.fill('input[placeholder="Nama Customer..."]', 'Customer Playwright');
  142 |     
  143 |     // Add Item
  144 |     await page.click('text=+ Tambah Item');
  145 |     
  146 |     // Select Product
  147 |     await page.locator('select.input').nth(3).selectOption({ label: 'Produk Testing Playwright' });
  148 |     
  149 |     // Fill Qty
  150 |     await page.fill('input[type="number"]', '20');
  151 |     
  152 |     await page.click('button:has-text("Simpan Outbound")');
  153 |     await expect(page.locator('text=SO-TEST-999')).toBeVisible({ timeout: 15000 });
  154 | 
  155 |     // 7. Pick & Ship Outbound
  156 |     await page.locator('tr').filter({ hasText: 'SO-TEST-999' }).locator('text=Detail').click();
  157 |     await page.click('button:has-text("Proses Pick")');
  158 |     await expect(page.locator('text=Status: PICKED')).toBeVisible({ timeout: 15000 });
  159 |     
  160 |     await page.click('button:has-text("Kirim (Ship)")');
  161 |     await expect(page.locator('text=Status: SHIPPED')).toBeVisible({ timeout: 15000 });
  162 | 
  163 |     // 8. Planogram Flow
  164 |     await page.click('a[href="/planograms"]');
  165 |     await expect(page).toHaveURL(/\/planograms/, { timeout: 15000 });
  166 |     
  167 |     // Wait for warehouse data to load
  168 |     await page.waitForTimeout(2000); 
  169 | 
  170 |     // Click 'Buat Planogram' main button
  171 |     await page.click('button:has-text("Buat Planogram")');
  172 |     
  173 |     // In the modal, select the warehouse
  174 |     await page.locator('.fixed.inset-0 select').first().selectOption({ label: whName });
  175 |     
  176 |     // Fill description
  177 |     await page.fill('input[placeholder="Deskripsi perubahan..."]', 'Initial Planogram Test Playwright');
  178 |     
  179 |     // Click Buat & Edit
  180 |     await page.click('button:has-text("Buat & Edit")');
  181 | 
  182 |     // It should navigate to PlanogramEditor
  183 |     await expect(page).toHaveURL(/\/planograms\/\d+/, { timeout: 15000 });
  184 |     await expect(page.locator(`text=${whName}`)).toBeVisible({ timeout: 15000 });
  185 | 
  186 |     // Search product in left sidebar
  187 |     await page.fill('input[placeholder="Cari nama/SKU/barcode..."]', 'Produk Testing');
  188 |     await expect(page.locator('text=Produk Testing').first()).toBeVisible({ timeout: 15000 });
  189 | 
  190 |     // Test creating a Zone using the canvas
  191 |     await page.click('button:has-text("Zone")');
  192 |     
  193 |     // Find the canvas to get its bounding box to click inside it
  194 |     const canvasContainer = page.locator('.konvajs-content');
  195 |     const box = await canvasContainer.boundingBox();
  196 |     if (box) {
  197 |       await page.mouse.move(box.x + 100, box.y + 100);
  198 |       await page.mouse.down();
  199 |       await page.mouse.move(box.x + 200, box.y + 200);
  200 |       await page.mouse.up();
  201 |     }
  202 | 
  203 |     // Save the planogram
  204 |     await page.click('button:has-text("Simpan")');
  205 |     await expect(page.locator('text=Tersimpan')).toBeVisible({ timeout: 15000 });
  206 | 
  207 |     // Test Snapshot functionality
  208 |     page.once('dialog', dialog => dialog.accept());
  209 |     await page.click('button:has-text("Snapshot")');
  210 | 
  211 |     // Wait for the snapshot process to complete
  212 |     await page.waitForTimeout(2000);
  213 | 
  214 |   });
  215 | });
  216 | 
  217 | 
```