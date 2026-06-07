<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Transfer Antar Gudang</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Stok', to: '/stock'}, {label: 'Transfer Stok'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
        + Buat Transfer Baru
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="store.transfers"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari nomor transfer..."
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
      @search="handleSearch"
    >
      <template #toolbar>
        <select v-model="filterStatus" class="input input-sm w-40" @change="fetchData(1)">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="executed">Executed</option>
          <option value="rejected">Rejected</option>
        </select>
      </template>

      <!-- Custom Cells -->
      <template #cell-transfer_number="{ row }">
        <span class="font-mono text-blue-600 font-medium">{{ row.transfer_number }}</span>
      </template>

      <template #cell-route="{ row }">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-700 font-semibold">{{ row.source_warehouse?.name }}</span>
          <ArrowRightIcon class="w-4 h-4 text-gray-400" />
          <span class="text-sm text-gray-700 font-semibold">{{ row.dest_warehouse?.name }}</span>
        </div>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :status="getStatusColor(value)" :label="value" class="uppercase text-xs" />
      </template>

      <template #cell-actions="{ row }">
        <button @click="openDetailModal(row)" class="btn btn-sm btn-ghost text-blue-600">
          Detail
        </button>
      </template>
    </DataTable>

    <!-- Create Transfer Modal -->
    <Modal v-model="showCreateModal" title="Buat Pengajuan Transfer" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Gudang Asal</label>
            <select v-model="form.source_warehouse_id" class="input w-full" required>
              <option value="">-- Pilih --</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Gudang Tujuan</label>
            <select v-model="form.dest_warehouse_id" class="input w-full" required>
              <option value="">-- Pilih --</option>
              <option v-for="wh in warehouses.filter(w => w.id !== form.source_warehouse_id)" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="label">Alasan Transfer</label>
          <input v-model="form.reason" class="input w-full" placeholder="Restock cabang, konsolidasi..." />
        </div>
        <div>
          <label class="label">Item yang ditransfer</label>
          <div class="space-y-2 mb-2">
            <div v-for="(item, idx) in form.items" :key="idx" class="flex gap-2 items-center">
              <select v-model="item.product_id" class="input w-full">
                <option value="">-- Pilih Produk --</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }}</option>
              </select>
              <input v-model.number="item.quantity" type="number" class="input w-24 text-center" placeholder="Qty" />
              <button @click="form.items.splice(idx, 1)" class="text-red-500 hover:text-red-700 p-2">
                <TrashIcon class="w-5 h-5" />
              </button>
            </div>
          </div>
          <button @click="form.items.push({product_id: '', quantity: 1})" class="btn btn-sm btn-outline">
            + Tambah Item
          </button>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showCreateModal = false" class="btn btn-outline">Batal</button>
          <button @click="submitCreate" class="btn btn-primary" :disabled="processing || form.items.length === 0 || !form.source_warehouse_id || !form.dest_warehouse_id">
            Buat Transfer
          </button>
        </div>
      </template>
    </Modal>

    <!-- Detail Modal -->
    <Modal v-model="showDetailModal" :title="`Detail Transfer: ${selectedTransfer?.transfer_number}`" size="lg">
      <div v-if="selectedTransfer" class="space-y-6">
        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
          <div>
            <p class="text-xs text-gray-500 mb-1">Rute Transfer</p>
            <p class="font-bold text-gray-800">{{ selectedTransfer.source_warehouse?.name }} &rarr; {{ selectedTransfer.dest_warehouse?.name }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Status</p>
            <StatusBadge :status="getStatusColor(selectedTransfer.status)" :label="selectedTransfer.status" class="uppercase text-xs" />
          </div>
          <div class="col-span-2">
            <p class="text-xs text-gray-500 mb-1">Alasan</p>
            <p class="text-sm text-gray-800">{{ selectedTransfer.reason || '-' }}</p>
          </div>
        </div>

        <div>
          <h4 class="font-bold text-gray-800 mb-3">Item Transfer</h4>
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="bg-gray-100 text-gray-600">
                <th class="p-2 border">Produk</th>
                <th class="p-2 border text-center">Kuantitas</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in selectedTransfer.items" :key="item.id">
                <td class="p-2 border">{{ item.product?.name }} <span class="text-xs text-gray-500">({{ item.product?.sku }})</span></td>
                <td class="p-2 border text-center font-semibold">{{ item.quantity }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-between w-full">
          <button @click="showDetailModal = false" class="btn btn-outline">Tutup</button>
          <div class="flex gap-2" v-if="selectedTransfer?.status === 'pending'">
            <button @click="handleAction('reject')" class="btn btn-outline border-red-200 text-red-600" :disabled="processing">Tolak</button>
            <button @click="handleAction('approve')" class="btn btn-primary" :disabled="processing">Setujui</button>
          </div>
          <div class="flex gap-2" v-if="selectedTransfer?.status === 'approved'">
            <button @click="handleAction('execute')" class="btn btn-emerald" :disabled="processing">Eksekusi Fisik (Diterima)</button>
          </div>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { warehouseAPI, productAPI } from '../services/api'
import { useTransferStore } from '../stores/transfer'
import DataTable from '../components/common/DataTable.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import Modal from '../components/common/Modal.vue'
import { useDebounce } from '../composables/useDebounce'
import { ArrowRightIcon, TrashIcon } from '@heroicons/vue/24/outline'

const store = useTransferStore()

const columns = [
  { key: 'transfer_number', label: 'No. Transfer', sortable: false },
  { key: 'route', label: 'Rute', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterStatus = ref('')
const currentSearch = ref('')
const processing = ref(false)

const warehouses = ref([])
const products = ref([])

const showCreateModal = ref(false)
const showDetailModal = ref(false)
const selectedTransfer = ref(null)

const form = ref({
  source_warehouse_id: '',
  dest_warehouse_id: '',
  reason: '',
  items: []
})

function getStatusColor(status) {
  const map = {
    'pending': 'warning',
    'approved': 'info',
    'executed': 'success',
    'rejected': 'danger'
  }
  return map[status?.toLowerCase()] || 'inactive'
}

async function fetchWarehousesAndProducts() {
  try {
    const whRes = await warehouseAPI.list({ per_page: 100 })
    warehouses.value = Array.isArray(whRes) ? whRes : (whRes.data || [])
    
    const prRes = await productAPI.list({ per_page: 500, is_active: 1 })
    products.value = prRes.data?.data || prRes.data || []
  } catch (e) {
    console.error(e)
  }
}

async function fetchData(page = 1) {
  const params = { page, per_page: 25 }
  if (filterStatus.value !== '') params.status = filterStatus.value
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

function openCreateModal() {
  form.value = {
    source_warehouse_id: '',
    dest_warehouse_id: '',
    reason: '',
    items: []
  }
  showCreateModal.value = true
}

async function submitCreate() {
  processing.value = true
  try {
    await store.create(form.value)
    showCreateModal.value = false
    fetchData()
  } finally {
    processing.value = false
  }
}

async function openDetailModal(row) {
  try {
    const data = await store.fetchOne(row.id)
    selectedTransfer.value = data?.data || data
    showDetailModal.value = true
  } catch (e) {
    // Error is handled in store
  }
}

async function handleAction(action) {
  processing.value = true
  try {
    if (action === 'approve') await store.approve(selectedTransfer.value.id)
    if (action === 'reject') await store.reject(selectedTransfer.value.id)
    if (action === 'execute') await store.execute(selectedTransfer.value.id)
    
    showDetailModal.value = false
    fetchData()
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  fetchWarehousesAndProducts()
  fetchData()
})
</script>
