<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Outbound (Pengiriman)</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Outbound'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
        + Buat Outbound
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="store.outbounds"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari referensi atau tujuan..."
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
      @search="handleSearch"
    >
      <template #toolbar>
        <select v-model="filterStatus" class="input input-sm w-40" @change="fetchData(1)">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="pending">Pending</option>
          <option value="picking">Picking</option>
          <option value="packing">Packing</option>
          <option value="ready">Ready</option>
          <option value="shipped">Shipped</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </template>

      <!-- Custom Cells -->
      <template #cell-reference_number="{ row }">
        <span class="font-mono text-indigo-600 font-medium">{{ row.reference_number || '-' }}</span>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :status="getStatusColor(value)" :label="value" class="uppercase text-xs" />
      </template>

      <template #cell-scheduled_date="{ value }">
        <span class="text-gray-600">{{ formatDate(value) }}</span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <router-link :to="`/outbounds/${row.id}`" class="btn btn-sm btn-ghost text-blue-600">
            Detail
          </router-link>
          <button v-if="row.status === 'ready'" @click="ship(row)" class="btn btn-sm btn-ghost text-emerald-600">
            Kirim (Ship)
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create Modal -->
    <Modal v-model="showModal" title="Buat Outbound Baru" size="lg">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Tipe Transaksi <span class="text-red-500">*</span></label>
          <select v-model="form.transaction_type" class="input">
            <option value="sales_order">Sales Order (SO)</option>
            <option value="return_supplier">Return ke Supplier</option>
            <option value="transfer_out">Transfer Out</option>
            <option value="other">Lainnya</option>
          </select>
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Gudang Asal <span class="text-red-500">*</span></label>
          <select v-model="form.warehouse_id" class="input">
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
          </select>
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Nomor Referensi <span class="text-red-500">*</span></label>
          <input v-model="form.reference_number" type="text" class="input" placeholder="SO-2023-001" />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Tanggal Jadwal</label>
          <input v-model="form.scheduled_date" type="date" class="input" />
        </div>
        <div class="col-span-2">
          <label class="label">Customer / Tujuan</label>
          <input v-model="form.destination_name" type="text" class="input" placeholder="Nama Customer/Tujuan..." />
        </div>
        <div class="col-span-2">
          <label class="label">Catatan</label>
          <textarea v-model="form.notes" rows="2" class="input resize-none" placeholder="Catatan opsional..."></textarea>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showModal = false" class="btn btn-outline">Batal</button>
          <button @click="save" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useOutboundStore } from '../stores/outbound'
import { warehouseAPI } from '../services/api'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { useDebounce } from '../composables/useDebounce'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const store = useOutboundStore()

const columns = [
  { key: 'reference_number', label: 'No. Referensi', sortable: false },
  { key: 'transaction_type', label: 'Tipe', sortable: false },
  { key: 'destination_name', label: 'Tujuan/Customer', sortable: false },
  { key: 'scheduled_date', label: 'Tgl. Jadwal', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterStatus = ref('')
const currentSearch = ref('')
const showModal = ref(false)
const saving = ref(false)
const warehouses = ref([])

const form = ref({
  warehouse_id: '',
  transaction_type: 'sales_order',
  reference_number: '',
  scheduled_date: '',
  destination_name: '',
  notes: ''
})

function getStatusColor(status) {
  const map = {
    'draft': 'inactive',
    'pending': 'warning',
    'picking': 'info',
    'packing': 'info',
    'ready': 'success',
    'shipped': 'active',
    'cancelled': 'danger'
  }
  return map[status?.toLowerCase()] || 'inactive'
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try { return format(new Date(dateStr), 'd MMM yyyy', { locale: id }) } catch { return dateStr }
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

async function fetchWarehouses() {
  try {
    const res = await warehouseAPI.list({ per_page: 100, is_active: 1 })
    warehouses.value = res.data || res
    if (warehouses.value.length > 0) {
      form.value.warehouse_id = warehouses.value[0].id
    }
  } catch (e) {
    console.error(e)
  }
}

function openCreateModal() {
  form.value = {
    warehouse_id: warehouses.value.length > 0 ? warehouses.value[0].id : '',
    transaction_type: 'sales_order',
    reference_number: '',
    scheduled_date: '',
    destination_name: '',
    notes: ''
  }
  showModal.value = true
}

async function save() {
  if (!form.value.reference_number || !form.value.warehouse_id) return
  saving.value = true
  try {
    await store.create(form.value)
    showModal.value = false
    fetchData(1)
  } catch (e) {
    // handled by store
  } finally {
    saving.value = false
  }
}

function ship(row) {
  store.ship(row.id, { notes: 'Auto shipped' }).then(() => fetchData(store.pagination.current_page))
}

onMounted(() => {
  fetchData()
  fetchWarehouses()
})
</script>
