<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Stock Opname</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Stok', to: '/stock'}, {label: 'Stock Opname'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-sm btn-primary">
        + Buat Baru
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="store.opnames"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari nomor opname..."
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
      @search="handleSearch"
    >
      <template #toolbar>
        <select v-model="filterStatus" class="input input-sm w-40" @change="fetchData(1)">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="in_progress">In Progress</option>
          <option value="submitted">Submitted</option>
          <option value="approved">Approved</option>
        </select>
      </template>

      <!-- Custom Cells -->
      <template #cell-opname_number="{ row }">
        <span class="font-mono text-blue-600 font-medium">{{ row.opname_number }}</span>
      </template>

      <template #cell-warehouse="{ row }">
        <span class="text-gray-700">{{ row.warehouse?.name }}</span>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :status="getStatusColor(value)" :label="value" class="uppercase text-xs" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <router-link :to="`/stock-opnames/${row.id}`" class="btn btn-sm btn-ghost text-blue-600">
            Detail
          </router-link>
        </div>
      </template>
    </DataTable>

    <!-- Modal: Create Stock Opname -->
    <Modal v-model="showModal" title="Buat Stock Opname Baru" size="md">
      <div class="space-y-4">
        <div>
          <label class="label">Gudang <span class="text-red-500">*</span></label>
          <select v-model="form.warehouse_id" class="input">
            <option value="">-- Pilih Gudang --</option>
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Tipe Opname</label>
            <select v-model="form.type" class="input">
              <option value="full">Full</option>
              <option value="partial">Partial</option>
            </select>
          </div>
          <div>
            <label class="label">Tanggal Mulai</label>
            <input v-model="form.start_date" type="date" class="input" />
          </div>
        </div>
        <div>
          <label class="label">Catatan</label>
          <textarea v-model="form.notes" class="input" rows="3" placeholder="Catatan opsional..."></textarea>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showModal = false" class="btn btn-outline">Batal</button>
          <button @click="createOpname" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : 'Buat Opname' }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useStockOpnameStore } from '../stores/stockOpname'
import { useWarehouseStore } from '../stores/warehouse'
import DataTable from '../components/common/DataTable.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import Modal from '../components/common/Modal.vue'
import { useNotificationStore } from '../stores/notification'
import { useDebounce } from '../composables/useDebounce'

const notify = useNotificationStore()
const store = useStockOpnameStore()
const warehouseStore = useWarehouseStore()

const columns = [
  { key: 'opname_number', label: 'No. Dokumen', sortable: false },
  { key: 'warehouse', label: 'Gudang', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterStatus = ref('')
const currentSearch = ref('')
const showModal = ref(false)
const submitting = ref(false)

const form = ref({
  warehouse_id: '',
  type: 'full',
  start_date: new Date().toISOString().slice(0, 10),
  notes: '',
})

const warehouses = ref([])

function getStatusColor(status) {
  const map = {
    'draft': 'inactive',
    'in_progress': 'warning',
    'submitted': 'info',
    'approved': 'success'
  }
  return map[status?.toLowerCase()] || 'inactive'
}

function openCreateModal() {
  form.value = {
    warehouse_id: '',
    type: 'full',
    start_date: new Date().toISOString().slice(0, 10),
    notes: '',
  }
  showModal.value = true
}

async function createOpname() {
  if (!form.value.warehouse_id) {
    notify.error('Pilih gudang terlebih dahulu')
    return
  }
  submitting.value = true
  try {
    await store.create(form.value)
    showModal.value = false
    await fetchData()
  } catch (e) {
    // Store already handles error notification
  } finally {
    submitting.value = false
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

onMounted(() => {
  fetchData()
  // Load warehouses for create form
  warehouseStore.fetchList({ per_page: 100 }).then(() => {
    warehouses.value = warehouseStore.warehouses || []
  })
})
</script>
