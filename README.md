<p align="center">
  <br>
  <img src="https://img.shields.io/badge/Laravel-11-%23FF2D20?logo=laravel" alt="Laravel 11">
  <img src="https://img.shields.io/badge/Vue.js-3.4-%234FC08D?logo=vuedotjs" alt="Vue.js 3">
  <img src="https://img.shields.io/badge/PostgreSQL-16-%234169E1?logo=postgresql" alt="PostgreSQL 16">
  <img src="https://img.shields.io/badge/Redis-7-%23DC382D?logo=redis" alt="Redis 7">
  <img src="https://img.shields.io/badge/Docker-Compose-%232496ED?logo=docker" alt="Docker Compose">
  <img src="https://img.shields.io/badge/License-MIT-blue" alt="MIT License">
</p>

<h1 align="center">🏭 WMS Multi-Gudang</h1>

<p align="center">
  <strong>Sistem Manajemen Gudang Multi-Warehouse Enterprise</strong>
  <br>
  Solusi lengkap untuk mengelola operasional pergudangan multi-cabang, mulai dari penerimaan barang, penyimpanan, pengeluaran, transfer antar gudang, hingga stok opname dan pelaporan.
</p>

---

## 📋 Daftar Isi

- [✨ Fitur Unggulan](#-fitur-unggulan)
- [🏗️ Arsitektur Sistem](#️-arsitektur-sistem)
- [🛠️ Tech Stack](#️-tech-stack)
- [📦 Struktur Direktori](#-struktur-direktori)
- [⚡ Quick Start](#-quick-start)
- [🔧 Konfigurasi](#-konfigurasi)
- [🗺️ Peta Rute API](#️-peta-rute-api)
- [📊 Modul Sistem](#-modul-sistem)
  - [🏢 Manajemen Gudang & Zonasi](#-manajemen-gudang--zonasi)
  - [📦 Manajemen Produk & Kategori](#-manajemen-produk--kategori)
  - [📥 Manajemen Penerimaan Barang (Inbound)](#-manajemen-penerimaan-barang-inbound)
  - [📤 Manajemen Pengeluaran Barang (Outbound)](#-manajemen-pengeluaran-barang-outbound)
  - [🔄 Transfer Antar Gudang](#-transfer-antar-gudang)
  - [📋 Stok Opname](#-stok-opname)
  - [📐 Planogram & Tata Letak](#-planogram--tata-letak)
  - [📈 Pelaporan & Analitik](#-pelaporan--analitik)
  - [🔔 Notifikasi & Alert](#-notifikasi--alert)
  - [📎 Manajemen Dokumen](#-manajemen-dokumen)
  - [🔙 Manajemen Retur](#-manajemen-retur)
  - [🪝 Webhook & Integrasi](#-webhook--integrasi)
  - [👥 Manajemen Pengguna & RBAC](#-manajemen-pengguna--rbac)
- [🧠 Logika Bisnis Inti](#-logika-bisnis-inti)
  - [FIFO & FEFO](#fifo--fefo)
  - [Mutasi Stok Teraudit](#mutasi-stok-teraudit)
  - [Multi-Warehouse Isolation](#multi-warehouse-isolation)
  - [Document Numbering](#document-numbering)
- [🔐 Keamanan](#-keamanan)
- [🐳 Docker](#-docker)
- [🤝 Kontribusi](#-kontribusi)
- [📄 Lisensi](#-lisensi)

---

## ✨ Fitur Unggulan

- **Multi-Warehouse** — Kelola banyak gudang (reguler, cold storage, bonded, konsinyasi) dalam satu sistem.
- **Zonasi & Racking** — Layout gudang diorganisir berdasarkan zona → rak → level → slot untuk visibilitas penuh.
- **Inbound & Outbound Lifecycle** — Alur penerimaan dan pengeluaran barang dengan status lengkap: draft → pending → partial/received → cancelled.
- **Transfer Antar Gudang** — Transfer stok antar warehouse dengan workflow approval.
- **Stok Opname** — Siklus opname: draft → in_progress → submitted → approved, dengan adjustment stok otomatis.
- **FIFO / FEFO Strategy** — Sistem pengeluaran barang menggunakan FIFO (First In First Out) atau FEFO (First Expiry First Out) untuk meminimalkan risiko kadaluarsa.
- **Planogram Visual** — Editor tata letak gudang berbasis Konva.js untuk pengaturan visual rack dan slot.
- **Audit Trail Lengkap** — Setiap mutasi stok tercatat dengan detail (siapa, kapan, dari mana, ke mana).
- **Role-Based Access Control** — Manajemen izin granular menggunakan Spatie Laravel Permission.
- **Notification Engine** — Notifikasi real-time untuk low stock, produk mendekati expiry, inbound/outbound overdue.
- **Webhook Integration** — Webhook outbound dengan retry mechanism, signature HMAC, dan exponential backoff.
- **Report & Export** — Laporan stok, mutasi, aging, expiry, utilisasi, valuasi — export ke PDF/Excel.
- **Search Engine** — Pencarian produk cepat dengan Meilisearch.
- **S3-Compatible Storage** — File storage menggunakan MinIO (S3-compatible).

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────────┐
│                          CLIENT LAYER                               │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │         Vue 3 SPA (Vite + Pinia + Vue Router)                │  │
│  │    TailwindCSS + Heroicons + Konva.js (Planogram)            │  │
│  └───────────────────────┬───────────────────────────────────────┘  │
│                          │ HTTP (Axios) / WebSocket (Laravel Reverb)│
├──────────────────────────┼──────────────────────────────────────────┤
│                    API GATEWAY (Laravel)                            │
│  ┌───────────────────────┴──────────────────────────────────────┐  │
│  │              API Routes (api/v1/)                            │  │
│  │  Sanctum Auth | RBAC Middleware | Request Validation        │  │
│  └───────┬────────────────────────────────────┬─────────────────┘  │
│          ▼                                    ▼                      │
│  ┌──────────────┐  ┌────────────┐  ┌──────────────────────┐        │
│  │ Controllers   │  │ Services   │  │ Policies / Resources │        │
│  │ (thin, 1 job) │──┤ (business) │──┤ (Authorization /     │        │
│  └──────────────┘  └─────┬──────┘  │  API Transform)      │        │
│                          │         └──────────────────────┘        │
│                          ▼                                         │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    MODELS / ELOQUENT ORM                     │  │
│  │    Warehouse → Zone → Rack → RackLevel → RackSlot → SlotStock│  │
│  │    Product ⟷ Inventory ⟷ StockTransaction                    │  │
│  │    Inbound/Outbound/Transfer/StockOpname/Return              │  │
│  └──────────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────┤
│                        DATA / INFRA LAYER                            │
│  ┌──────────┐ ┌──────┐ ┌────────┐ ┌─────────┐ ┌──────────────┐   │
│  │PostgreSQL│ │Redis │ │ MinIO  │ │Meilisearch││ Laravel      │   │
│  │(PostGIS) │ │(Cache│ │(S3     │ │(Full-text││ Horizon      │   │
│  │          │ │+Queue)│ │Storage)│ │ Search)  ││ (Queue)      │   │
│  └──────────┘ └──────┘ └────────┘ └─────────┘ └──────────────┘   │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │              Laravel Reverb (WebSocket)                       │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Tech Stack

### Backend

| Teknologi | Kegunaan |
|-----------|----------|
| **Laravel 11** | Framework PHP utama dengan PHP 8.3 |
| **PostgreSQL 16 + PostGIS** | Database relasional utama |
| **Redis 7** | Cache, session driver, queue (Laravel Horizon) |
| **Laravel Sanctum** | SPA authentication (cookie-based) |
| **Spatie Laravel Permission** | RBAC — role & permission management |
| **Laravel Scout + Meilisearch** | Full-text search engine untuk produk |
| **Laravel Horizon** | Queue monitoring dashboard |
| **Laravel Reverb** | WebSocket server real-time |
| **MinIO** | S3-compatible object storage untuk dokumen |
| **dompdf / PhpSpreadsheet** | Export laporan ke PDF & Excel |

### Frontend

| Teknologi | Kegunaan |
|-----------|----------|
| **Vue 3** (Composition API) | Frontend framework |
| **Vite 5** | Build tool & dev server |
| **Pinia** | State management |
| **Vue Router 4** | Routing SPA |
| **Tailwind CSS 3** | Utility-first CSS framework |
| **Heroicons** | Icons set |
| **Konva.js + vue-konva** | Planogram editor kanvas |
| **Axios** | HTTP client |
| **date-fns** | Date utilities |
| **Playwright** | E2E testing |

### Infrastructure

| Teknologi | Kegunaan |
|-----------|----------|
| **Docker Compose** | Container orchestration |
| **Nginx** | Reverse proxy |
| **PHP-FPM** | Application server |

---

## 📦 Struktur Direktori

```
wms-project/
├── backend/                          # Laravel API Application
│   ├── app/
│   │   ├── Console/Commands/         # Artisan commands (notifications, webhooks)
│   │   ├── Enums/                    # PHP Enums (status, tipe, strategy)
│   │   ├── Events/                   # Event classes (low stock, expiry, overdue)
│   │   ├── Exceptions/               # Custom exceptions (InsufficientStock)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/  # API Controllers (30+ controllers)
│   │   │   ├── Middleware/           # CORS, ForceJson, CheckPermission, CheckRole
│   │   │   ├── Requests/            # Form request validation
│   │   │   └── Resources/           # API Resources (transformers)
│   │   ├── Listeners/               # Event listeners (webhook dispatch)
│   │   ├── Models/                  # Eloquent models (45+ models)
│   │   ├── Policies/                # Authorization policies
│   │   ├── Providers/               # Service providers
│   │   └── Services/                # Business logic layer (13 services)
│   ├── config/                      # Laravel config files
│   ├── database/
│   │   ├── migrations/              # 25 migration files
│   │   └── seeders/                 # Database seeders
│   ├── routes/
│   │   └── api.php                  # API route definitions (v1)
│   ├── tests/                       # PHPUnit tests
│   ├── docker/                      # Docker config files
│   ├── Dockerfile
│   └── nginx.conf / php.ini
│
├── frontend/                        # Vue 3 SPA
│   ├── src/
│   │   ├── components/              # Reusable components
│   │   │   ├── common/              # Table, Modal, Pagination, Toast, dll
│   │   │   ├── planogram/           # MiniPlanogram viewer
│   │   │   ├── warehouse/           # ZoneModal
│   │   │   ├── notification/        # NotificationBell
│   │   │   ├── reports/             # ExportButtons, UtilizationTab, ValuationTab
│   │   │   └── returns/             # ReturnForm
│   │   ├── composables/             # Vue composables (useApi, useDebounce, usePermission)
│   │   ├── layouts/                 # MainLayout (sidebar, navbar)
│   │   ├── router/                  # Vue Router config
│   │   ├── services/                # Axios API service
│   │   ├── stores/                  # Pinia stores (23 stores)
│   │   └── views/                   # Page components (27 views)
│   ├── e2e/                         # Playwright E2E tests
│   ├── Dockerfile
│   └── nginx/                       # Frontend nginx config
│
├── docker/                          # Shared Docker resources
│   ├── nginx/                       # Nginx reverse proxy config
│   └── startup.sh
├── nginx/                           # Laravel nginx config
├── docker-compose.yml               # Main orchestration (9 services)
├── Makefile                         # Make commands (up, down, migrate, etc.)
└── .env / .env.docker               # Environment configuration
```

---

## ⚡ Quick Start

### Prasyarat

- Docker & Docker Compose
- Git
- Make (opsional)

### Instalasi & Setup

```bash
# 1. Clone repository
git clone https://github.com/your-org/wms-multi-gudang.git
cd wms-multi-gudang

# 2. Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 3. Build & start semua service
make up

# 4. Setup aplikasi (generate key, migrate, seed)
make setup

# 5. Akses aplikasi
#    Frontend : http://localhost:5173
#    Backend  : http://localhost:8000
#    MinIO    : http://localhost:9001
#    Meili    : http://localhost:7700
#    Horizon  : http://localhost:8000/horizon
```

### Atau tanpa Makefile

```bash
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed
```

### Perintah Penting

```bash
make up              # Start semua services
make down            # Stop semua services
make logs            # Tail semua logs
make migrate         # Jalankan migration
make seed            # Seed database
make queue           # Jalankan queue worker
make reverb          # Start WebSocket server
make clean           # Hapus semua container + volume
```

---

## 🔧 Konfigurasi

Konfigurasi utama ada di `backend/.env` (atau `.env.docker` untuk Docker):

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=wms
DB_USERNAME=wms
DB_PASSWORD=wms_secret

# Redis (Cache + Session + Queue)
REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Storage
FILESYSTEM_DISK=minio
MINIO_ENDPOINT=minio:9000
MINIO_ACCESS_KEY=minioadmin
MINIO_SECRET_KEY=minioadmin
MINIO_BUCKET=wms

# Search
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey

# WebSocket
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
```

---

## 🗺️ Peta Rute API

Semua endpoint API berada di prefix `/api/v1/` dan mayoritas dilindungi middleware `auth:sanctum`.

### Autentikasi
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/login` | Login (SPA session) |
| POST | `/logout` | Logout |
| POST | `/register` | Registrasi |
| GET | `/me` | Profile user saat ini |
| PUT | `/profile` | Update profile |
| PUT | `/password` | Update password |

### Dashboard
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/dashboard` | Ringkasan dashboard |

### Manajemen Warehouse
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/warehouses` | List / Buat warehouse |
| GET/PUT/DELETE | `/warehouses/{id}` | Detail / Update / Hapus |
| GET | `/warehouses/{id}/summary` | Ringkasan gudang |
| GET | `/warehouses/{id}/utilization` | Utilisasi kapasitas |
| GET/POST | `/warehouses/{id}/zones` | List / Buat zona |
| PUT | `/zones/{zone}/activate` | Aktifkan zona |
| PUT | `/zones/{zone}/deactivate` | Nonaktifkan zona |
| GET/POST | `/zones/{zone}/racks` | List / Buat rak |
| PUT | `/racks/{rack}/position` | Update posisi rak |
| GET | `/racks/{rack}/slots` | List slot dalam rak |

### Produk
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/products` | List / Buat produk |
| GET/PUT/DELETE | `/products/{id}` | Detail / Update / Hapus |
| GET | `/products/search` | Pencarian produk |
| POST | `/products/import` | Import massal |
| GET | `/products/{id}/locations` | Lokasi penyimpanan produk |

### Inventory & Stok
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/inventory` | Data inventory |
| GET | `/inventory/stock` | Ringkasan stok |
| GET | `/inventory/alerts` | Alert stok |
| GET | `/inventory/trace/{sku}` | Traceability produk |

### Inbound (Penerimaan)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/inbounds` | List / Buat inbound |
| GET/PUT/DELETE | `/inbounds/{id}` | Detail / Update / Hapus |
| POST | `/inbounds/{id}/receive` | Proses penerimaan (partial/full) |
| POST | `/inbounds/{id}/cancel` | Batalkan inbound |

### Outbound (Pengeluaran)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/outbounds` | List / Buat outbound |
| GET/PUT/DELETE | `/outbounds/{id}` | Detail / Update / Hapus |
| POST | `/outbounds/{id}/pick` | Proses picking |
| POST | `/outbounds/{id}/ship` | Proses pengiriman |
| POST | `/outbounds/{id}/cancel` | Batalkan outbound |

### Transfer Antar Gudang
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/transfers` | List / Buat transfer |
| GET/PUT/DELETE | `/transfers/{id}` | Detail / Update / Hapus |
| POST | `/transfers/{id}/approve` | Approve transfer |
| POST | `/transfers/{id}/reject` | Tolak transfer |
| POST | `/transfers/{id}/execute` | Eksekusi transfer |

### Stok Opname
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/stock-opnames` | List / Buat opname |
| GET/PUT/DELETE | `/stock-opnames/{id}` | Detail / Update / Hapus |
| POST | `/stock-opnames/{id}/start` | Mulai opname |
| POST | `/stock-opnames/{id}/submit` | Submit hasil opname |
| POST | `/stock-opnames/{id}/approve` | Approve opname (auto-adjust stok) |

### Retur
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/returns` | List / Buat retur |
| GET/PUT/DELETE | `/returns/{id}` | Detail / Update / Hapus |
| POST | `/returns/{id}/submit` | Submit retur |
| POST | `/returns/{id}/approve` | Approve retur |
| POST | `/returns/{id}/process` | Proses retur (restock) |
| POST | `/returns/{id}/reject` | Tolak retur |
| POST | `/returns/{id}/cancel` | Batalkan retur |

### Planogram
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/PUT | `/warehouses/{id}/planogram` | Detail / Update planogram |
| POST | `/warehouses/{id}/planogram/snapshot` | Simpan snapshot |
| GET | `/warehouses/{id}/planogram/history` | Riwayat snapshot |
| GET | `/planogram/search` | Cari produk di planogram |

### Report
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/reports/stock` | Laporan stok |
| GET | `/reports/mutations` | Laporan mutasi |
| GET | `/reports/aging` | Aging inventory |
| GET | `/reports/expiry` | Produk mendekati expiry |
| GET | `/reports/utilization` | Utilisasi gudang |
| GET | `/reports/valuation` | Valuasi stok |
| GET | `/reports/activity` | Aktivitas pengguna |
| POST | `/reports/export` | Export laporan (PDF/Excel) |

### Pengaturan & Integrasi
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/users` | Manajemen pengguna |
| GET | `/roles` | Daftar roles |
| GET | `/permissions` | Daftar permissions |
| GET/POST | `/categories` | Manajemen kategori |
| GET/POST/DELETE | `/documents` | Manajemen dokumen |
| GET | `/audit-logs` | Audit trail |
| GET/POST/PUT/DELETE | `/webhooks` | Manajemen webhook |
| POST | `/webhooks/{id}/test` | Test webhook |
| GET | `/webhooks/{id}/deliveries` | Riwayat pengiriman webhook |
| GET/PUT | `/settings` | Pengaturan sistem |
| GET | `/notifications` | Daftar notifikasi |
| GET | `/notifications/unread-count` | Jumlah notifikasi belum dibaca |
| PUT | `/notifications/{id}/read` | Tandai sudah dibaca |
| PUT | `/notifications/read-all` | Tandai semua sudah dibaca |
| GET/POST/PUT/DELETE | `/rack-slots` | Manajemen slot rak |
| PUT | `/rack-slots/{slot}/assign` | Assign produk ke slot |
| PUT | `/rack-slots/{slot}/unassign` | Unassign produk dari slot |
| GET/POST/PUT/DELETE | `/bins` | Manajemen bin/lokasi |

---

## 📊 Modul Sistem

### 🏢 Manajemen Gudang & Zonasi

**Warehouse** adalah entitas utama dalam sistem multi-gudang. Setiap gudang memiliki tipe yang menentukan perilaku operasionalnya:

| Tipe | Deskripsi |
|------|-----------|
| `reguler` | Gudang standar suhu ruang |
| `cold_storage` | Gudang berpendingin (rantai dingin) |
| `bonded` | Gudang berikat / TPB (Tempat Penimbunan Berikat) |
| `konsinyasi` | Gudang konsinyasi (barang milik supplier dititipkan) |

**Hierarki Penyimpanan:**

```
Warehouse (Gudang)
  └── Zone (Zona) — tipe, suhu, kelembaban
       └── Rack (Rak) — kapasitas, posisi koordinat
            └── RackLevel (Level Rak)
                 └── RackSlot (Slot Rak) — slot spesifik untuk produk
                      └── SlotStock — stok per slot
```

### 📦 Manajemen Produk & Kategori

- **Produk** memiliki SKU unik, barcode, dimensi, berat, dan pengaturan stok (min/max/reorder point/safety stock).
- **Batch & Expiry Tracking** — Produk dapat dikonfigurasi untuk track batch dan expiry date.
- **Multi-Barcode** — Satu produk bisa memiliki banyak barcode.
- **Multi-UOM** — Dukungan satuan dengan konversi (UOM Conversion).
- **Kategori** — Kategorisasi produk dengan struktur sederhana.
- **Pencarian** — Produk dapat dicari via Meilisearch atau database ilike.

### 📥 Manajemen Penerimaan Barang (Inbound)

Alur inbound:

```
Draft → Pending → Partial (opsional) → Received → Cancelled (kapan saja)
```

- Menerima barang dari **supplier** dengan nomor dokumen inbound.
- Mendukung **partial receive** — menerima barang secara bertahap.
- Sistem otomatis **membuat Goods Receipt (GR)** dan menambah inventory stok.
- Mencatat **batch number** dan **expiry date** untuk produk yang membutuhkan.
- Notifikasi otomatis saat inbound selesai diproses.

### 📤 Manajemen Pengeluaran Barang (Outbound)

Alur outbound:

```
Draft → Pending → Picking → Shipped → Cancelled (kapan saja)
```

- Pengeluaran barang ke **customer**.
- Mendukung **FIFO (First In First Out)** dan **FEFO (First Expiry First Out)** untuk picking strategy.
- Sistem memecah pengambilan stok per batch inventory sesuai strategi.
- Membuat **Goods Issue (GI)** dan mengurangi inventory secara otomatis.
- Notifikasi otomatis saat outbound dikirim.

### 🔄 Transfer Antar Gudang

Alur transfer:

```
Draft → Pending → Approved → Completed → Rejected (kapan saja)
```

- **Multi-warehouse stock transfer** — pindahkan stok dari gudang sumber ke gudang tujuan.
- Memerlukan **approval** sebelum eksekusi.
- Eksekusi membuat **Transfer (TR)** transaction — debit gudang sumber, kredit gudang tujuan.
- Status approval memastikan tidak ada transfer tidak sah.

### 📋 Stok Opname

Alur opname:

```
Draft → In Progress → Submitted → Approved → Cancelled (kapan saja)
```

- **Siklus opname lengkap**: perencanaan → pelaksanaan → verifikasi → approval.
- Sistem mencatat **system_qty** (stok tercatat), **actual_qty** (stok fisik), dan **variance** (selisih).
- Setelah **approve**, sistem otomatis membuat **Adjustment (ADJ+/ADJ-)** untuk menyesuaikan stok.
- Dilindungi dari negative stock — variance tidak boleh membuat stok negatif.

### 📐 Planogram & Tata Letak

- **Planogram visual** menggunakan Konva.js untuk mengatur tata letak rak dan slot di gudang.
- Setiap warehouse memiliki satu planogram.
- Mendukung **snapshot history** — simpan dan bandingkan tata letak dari waktu ke waktu.
- **Search produk** dalam planogram untuk menemukan lokasi barang dengan cepat.

### 📈 Pelaporan & Analitik

7 jenis laporan yang dapat di-export ke PDF/Excel:

| Laporan | Deskripsi |
|---------|-----------|
| **Stock Report** | Stok per produk per gudang |
| **Mutations** | Riwayat mutasi stok berdasarkan periode |
| **Aging** | Analisis umur inventory (berapa lama barang di gudang) |
| **Expiry** | Produk mendekati tanggal kadaluarsa |
| **Utilization** | Utilisasi kapasitas gudang |
| **Valuation** | Valuasi stok berdasarkan biaya |
| **Activity** | Log aktivitas pengguna |

### 🔔 Notifikasi & Alert

Sistem notifikasi terintegrasi dengan **4 jenis alert rule**:

| Alert | Deskripsi |
|-------|-----------|
| **Low Stock** | Produk dengan stok di bawah minimum |
| **Expiring Products** | Batch produk mendekati expiry date |
| **Overdue Inbounds** | Inbound yang belum diproses melebihi batas waktu |
| **Overdue Outbounds** | Outbound yang belum diproses melebihi batas waktu |

- Notifikasi dikirim ke user dengan role **Super Admin**, **Admin**, dan **Warehouse Manager**.
- **Duplicate prevention** — notifikasi yang sama tidak dikirim dalam 24 jam.
- **Event-driven** — setiap alert memicu event Laravel untuk integrasi lebih lanjut.
- **Scheduled checks** — pengecekan otomatis via scheduler setiap jam.

### 📎 Manajemen Dokumen

- Upload dokumen terkait operasional gudang (foto barang, dokumen pengiriman, dll).
- File disimpan di **MinIO (S3-compatible storage)**.
- Dokumen dapat dilampirkan ke berbagai entitas (inbound, outbound, dll).

### 🔙 Manajemen Retur

Alur retur:

```
Draft → Pending → Approved → Processed → Rejected → Cancelled
```

- Mendukung retur dari **customer** (sales return) dan ke **supplier** (purchase return).
- Setiap item retur memiliki **kondisi** (good/damaged) dan **resolusi** (restock/replace/refund/dispose).
- **Restock** — barang yang dikembalikan secara otomatis masuk ke inventory.
- **Refund tracking** — catat jumlah refund per item.

### 🪝 Webhook & Integrasi

Webhook outbound untuk integrasi dengan sistem eksternal:

- **Event subscription** — webhook dapat berlangganan event spesifik (inbound.received, outbound.shipped, dll).
- **HMAC-SHA256 Signature** — Setiap payload ditandatangani untuk verifikasi.
- **Exponential Backoff Retry** — Gagal kirim? Sistem akan retry dengan delay: 30s → 60s → 120s → 240s → 480s (max 5 attempts).
- **Delivery Log** — Riwayat pengiriman, response code, error message.
- **Manual Retry** — Retry delivery dari dashboard.

### 👥 Manajemen Pengguna & RBAC

- **Role-based access control** menggunakan Spatie Laravel Permission.
- Role bawaan: **Super Admin**, **Admin**, **Warehouse Manager**, **Staff**, **Viewer**.
- Policy-based authorization untuk setiap resource (Product, Warehouse, Inbound, dll).
- Setiap entitas operasional memiliki **created_by** dan **approved_by** untuk accountability.

---

## 🧠 Logika Bisnis Inti

### FIFO & FEFO

Sistem inventory mendukung dua strategi pengambilan stok:

- **FIFO (First In First Out)** — Barang yang lebih dulu masuk akan lebih dulu dikeluarkan. Cocok untuk produk non-perishable.
- **FEFO (First Expiry First Out)** — Barang dengan expiry date paling dekat akan dikeluarkan lebih dulu. Cocok untuk produk dengan masa berlaku.

Implementasi terdapat di `InventoryService::issueStock()` dengan query ordering:

```php
// FEFO
$query->orderByRaw('expiry_date ASC NULLS LAST')
      ->orderBy('created_at', 'ASC');

// FIFO
$query->orderBy('created_at', 'ASC')
      ->orderBy('id', 'ASC');
```

### Mutasi Stok Teraudit

Setiap perubahan stok dicatat sebagai `StockTransaction` dengan detail:

| Field | Deskripsi |
|-------|-----------|
| `transaction_type` | GR / GI / TR / ADJ+ / ADJ- / LT / RS / RC |
| `stock_before` | Jumlah stok sebelum perubahan |
| `stock_after` | Jumlah stok setelah perubahan |
| `reference_number` | Nomor dokumen referensi (inbound/outbound/transfer) |
| `created_by` | User yang melakukan transaksi |
| `quantity_in_base_uom` | Kuantitas dalam satuan dasar (setelah konversi) |

Tipe transaksi yang tercatat:

| Tipe | Deskripsi |
|------|-----------|
| `GR` | Goods Receipt (penerimaan barang) |
| `GI` | Goods Issue (pengeluaran barang) |
| `TR` | Transfer (antar gudang) |
| `LT` | Location Transfer (dalam satu gudang) |
| `ADJ+` | Adjustment Plus (penambahan) |
| `ADJ-` | Adjustment Minus (pengurangan) |
| `RS` | Reserve (reservasi stok) |
| `RC` | Release (pembatalan reservasi) |

### Multi-Warehouse Isolation

Stok diisolasi per warehouse — setiap warehouse memiliki data inventory sendiri. Transfer antar warehouse dicatat sebagai satu transaksi **TR** yang mendebit gudang sumber dan mengkredit gudang tujuan dalam satu atomic transaction.

### Document Numbering

Setiap dokumen (inbound, outbound, transfer, stock opname, retur) memiliki nomor unik yang digenerate oleh `DocumentSequenceService` dengan format yang dapat dikonfigurasi.

---

## 🔐 Keamanan

- **Sanctum SPA Authentication** — Autentikasi berbasis cookie httpOnly untuk keamanan XSS.
- **CSRF Protection** — Setiap request SPA melewati CSRF token.
- **Role-Based Access Control** — Setiap resource dilindungi policy + permission.
- **Request Validation** — Form request validation di setiap endpoint.
- **CORS Middleware** — Pembatasan akses dari origin yang tidak dikenal.
- **Force JSON Response** — Semua response dalam format JSON.
- **Soft Deletes** — Data tidak benar-benar dihapus untuk audit trail.
- **Negative Stock Protection** — Sistem menolak transaksi yang menyebabkan stok negatif.

---

## 🐳 Docker

Layanan yang berjalan dalam Docker Compose:

| Service | Container | Port |
|---------|-----------|------|
| **app** (PHP-FPM) | `wms-app` | — |
| **nginx** (backend) | `wms-nginx` | `8000:80` |
| **postgres** | `wms-postgres` | `5432` |
| **redis** | `wms-redis` | `6379` |
| **minio** | `wms-minio` | `9000` (API), `9001` (Console) |
| **meilisearch** | `wms-meilisearch` | `7700` |
| **horizon** | `wms-horizon` | — |
| **reverb** (WebSocket) | `wms-reverb` | `8080` |
| **frontend** | `wms-frontend` | `5173` |

---

## 🤝 Kontribusi

Kami menyambut kontribusi dari semua pihak! Untuk mulai berkontribusi:

1. Fork repository ini
2. Buat branch fitur: `git checkout -b feature/amazing-feature`
3. Commit perubahan: `git commit -m 'feat: add amazing feature'`
4. Push ke branch: `git push origin feature/amazing-feature`
5. Buka Pull Request

Mohon ikuti standar coding:
- **PSR-12** untuk PHP / Laravel
- **ESLint + Prettier** untuk Vue / JavaScript
- Tulis test untuk setiap fitur baru
- Update dokumentasi jika diperlukan

---

## 📄 Lisensi

Distributed under the **MIT License**. See `LICENSE` for more information.

---

<p align="center">
  <sub>Built with ❤️ using Laravel 11 & Vue 3</sub>
  <br>
  <sub>© 2026 WMS Multi-Gudang Team</sub>
</p>
