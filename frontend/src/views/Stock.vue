<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Inventori & Stok Gudang</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Stok'}]" class="mt-1" />
      </div>
      <div class="flex gap-2">
        <button @click="showTransferModal = true" class="btn btn-outline shadow-sm hover:shadow-md transition-shadow">
          <ArrowsRightLeftIcon class="w-4 h-4" /> Transfer Stok
        </button>
        <button @click="showAdjustModal = true" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
          + Penyesuaian (Adjusment)
        </button>
      </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card p-5 border-l-4 border-l-blue-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Nilai Stok</p>
        <p class="text-2xl font-bold text-gray-800">Rp {{ formatNumber(store.summary?.total_value || 0) }}</p>
      </div>
      <div class="card p-5 border-l-4 border-l-emerald-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Kuantitas</p>
        <p class="text-2xl font-bold text-gray-800">{{ formatNumber(store.summary?.total_quantity || 0) }} <span class="text-sm font-normal text-gray-500">unit</span></p>
      </div>
      <div class="card p-5 border-l-4 border-l-orange-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Low Stock Alert</p>
        <p class="text-2xl font-bold text-orange-600">{{ store.lowStock?.length || 0 }} <span class="text-sm font-normal text-gray-500">SKU</span></p>
      </div>
      <div class="card p-5 border-l-4 border-l-purple-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Stok Kadaluarsa</p>
        <p class="text-2xl font-bold text-purple-600">{{ store.summary?.expired_count || 0 }} <span class="text-sm font-normal text-gray-500">batch</span></p>
      </div>
    </div>

    <!-- Data Table -->
    <div class="card">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-800">Buku Stok (Ledger)</h3>
        <button @click="exportData" class="btn btn-sm btn-outline">
          <ArrowDownTrayIcon class="w-4 h-4" /> Export CSV
        </button>
      </div>
      <DataTable
        :columns="columns"
        :data="store.stocks"
        :loading="store.loading"
        :searchable="true"
        search-placeholder="Cari SKU atau Nama Produk..."
        :paginated="true"
        :pagination="store.pagination"
        @page-change="handlePageChange"
        @search="handleSearch"
      >
        <template #toolbar>
          <select v-model="filterWarehouse" class="input input-sm w-48" @change="fetchData(1)">
            <option value="">Semua Gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
          </select>
        </template>

        <!-- Custom Cells -->
        <template #cell-product="{ row }">
          <div>
            <router-link :to="`/products/${row.product_id}`" class="font-medium text-gray-800 hover:text-blue-600 transition-colors">
              {{ row.product?.name || row.product_name }}
            </router-link>
            <p class="text-xs text-gray-500 font-mono mt-0.5">{{ row.product?.sku || row.sku || '-' }}</p>
          </div>
        </template>

        <template #cell-location="{ row }">
          <span class="text-gray-700">{{ row.warehouse?.name || row.warehouse_name }}</span>
          <p class="text-xs text-gray-500 mt-0.5">{{ row.zone_name }} <span v-if="row.rack_code">/ {{ row.rack_code }}</span></p>
        </template>

        <template #cell-quantity="{ row }">
          <div class="flex items-center gap-2">
            <span class="font-semibold" :class="row.quantity <= (row.product?.min_stock || 0) ? 'text-orange-600' : 'text-gray-800'">
              {{ formatNumber(row.quantity) }}
            </span>
            <span class="text-xs text-gray-400">{{ row.product?.unit?.symbol || 'unit' }}</span>
          </div>
        </template>

        <template #cell-status="{ row }">
          <StatusBadge :status="row.quantity > 0 ? 'active' : 'inactive'" :label="row.quantity > 0 ? 'Tersedia' : 'Kosong'" />
        </template>

      </DataTable>
    </div>

    <!-- Modals Placeholders -->
    <Modal v-model="showTransferModal" title="Transfer Stok Antar Lokasi" size="lg">
      <div class="text-center py-8 text-gray-500">
        <p>Fitur pemindahan stok akan tersedia melalui modul Transfer Antar Gudang.</p>
        <button @click="showTransferModal = false" class="btn btn-outline mt-4">Tutup</button>
      </div>
    </Modal>

    <!-- Adjustment Modal uses Stock Opname creation -->
    <Modal v-model="showAdjustModal" title="Penyesuaian Stok (Adjusment)" size="md">
      <div class="space-y-4">
        <p class="text-sm text-gray-500 mb-4">
          Penyesuaian stok harus melalui proses <strong>Stock Opname</strong>. Form ini akan membuat dokumen Stock Opname baru dengan status Draft.
        </p>
        <div>
          <label class="label">Pilih Gudang Target</label>
          <select v-model="adjustForm.warehouse_id" class="input w-full" required>
            <option value="">-- Pilih Gudang --</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Catatan / Keterangan</label>
          <textarea v-model="adjustForm.notes" class="input w-full h-24" placeholder="Misal: Penyesuaian stok rusak bulan Juni..."></textarea>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="showAdjustModal = false" class="btn btn-outline">Batal</button>
          <button @click="submitAdjustment" :disabled="!adjustForm.warehouse_id || submitting" class="btn btn-primary">
            {{ submitting ? 'Memproses...' : 'Buat Stock Opname' }}
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStockStore } from '../stores/stock'
import { warehouseAPI, stockOpnameAPI } from '../services/api'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { useDebounce } from '../composables/useDebounce'
import { useNotificationStore } from '../stores/notification'
import { ArrowDownTrayIcon, ArrowsRightLeftIcon } from '@heroicons/vue/24/outline'

const store = useStockStore()
const router = useRouter()
const notify = useNotificationStore()

const columns = [
  { key: 'product', label: 'Produk', sortable: false },
  { key: 'location', label: 'Lokasi (Gudang/Zona)', sortable: false },
  { key: 'batch_number', label: 'Batch/Lot', sortable: false },
  { key: 'quantity', label: 'Kuantitas', sortable: false },
  { key: 'status', label: 'Status', sortable: false }
]

const filterWarehouse = ref('')
const currentSearch = ref('')
const warehouses = ref([])
const showTransferModal = ref(false)
const showAdjustModal = ref(false)

const submitting = ref(false)
const adjustForm = ref({
  warehouse_id: '',
  notes: ''
})

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num)
}

async function fetchWarehouses() {
  try {
    const res = await warehouseAPI.list({ per_page: 100, is_active: 1 })
    warehouses.value = res.data || res
  } catch (e) {
    console.error(e)
  }
}

async function fetchData(page = 1) {
  const params = { page, per_page: 25 }
  if (filterWarehouse.value !== '') params.warehouse_id = filterWarehouse.value
  if (currentSearch.value) params.search = currentSearch.value
  await store.fetchList(params)
}

function handlePageChange(page) {
  fetchData(page)
}

const { debounce } = useDebounce()
function handleSearch(query) {
  currentSearch.value = query
  debounce(() => {
    fetchData(1)
  }, 500)
}

function exportData() {
  alert('Fitur export sedang disiapkan')
}

async function submitAdjustment() {
  if (!adjustForm.value.warehouse_id) return
  submitting.value = true
  try {
    const res = await stockOpnameAPI.create(adjustForm.value)
    notify.success('Stock Opname (Draft) berhasil dibuat!')
    showAdjustModal.value = false
    adjustForm.value = { warehouse_id: '', notes: '' }
    // Could route to opname detail page if implemented, e.g.:
    // router.push(`/stock-opnames/${res.data.id}`)
  } catch (e) {
    notify.error('Gagal membuat Stock Opname')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchWarehouses()
  fetchData()
  store.fetchSummary()
  store.fetchLowStock()
})
</script>