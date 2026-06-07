<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Inbound (Penerimaan)</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Inbound'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
        + Buat Inbound
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="store.inbounds"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari referensi atau kode..."
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
      @search="handleSearch"
    >
      <template #toolbar>
        <select v-model="filterStatus" class="input input-sm w-40" @change="fetchData(1)">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="received">Received</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </template>

      <!-- Custom Cells -->
      <template #cell-inbound_number="{ row }">
        <span class="font-mono text-blue-600 font-medium">{{ row.inbound_number || row.reference_number || '-' }}</span>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :status="getStatusColor(value)" :label="value" class="uppercase text-xs" />
      </template>

      <template #cell-expected_date="{ value }">
        <span class="text-gray-600">{{ formatDate(value) }}</span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <router-link :to="`/inbounds/${row.id}`" class="btn btn-sm btn-ghost text-blue-600">
            Detail
          </router-link>
          <button v-if="row.status === 'pending'" @click="quickReceive(row)" class="btn btn-sm btn-ghost text-emerald-600">
            Receive
          </button>
          <button v-if="row.status === 'pending'" @click="cancelInbound(row)" class="btn btn-sm btn-ghost text-red-500">
            Batal
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create Modal -->
    <Modal v-model="showModal" title="Buat Inbound Baru" size="xl">
      <div class="space-y-6">
        <!-- Header Fields -->
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label class="label">Tipe Transaksi <span class="text-red-500">*</span></label>
            <select v-model="form.source_type" class="input">
              <option value="purchase_order">Purchase Order (PO)</option>
              <option value="return">Return Customer</option>
              <option value="transfer_in">Transfer In</option>
              <option value="other">Lainnya</option>
            </select>
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label class="label">Gudang Tujuan <span class="text-red-500">*</span></label>
            <select v-model="form.warehouse_id" class="input">
              <option value="">-- Pilih Gudang --</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label class="label">Nomor Referensi Sumber</label>
            <input v-model="form.source_reference" type="text" class="input" placeholder="PO-2023-001" />
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label class="label">Tanggal Diharapkan</label>
            <input v-model="form.expected_date" type="date" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">Catatan</label>
            <textarea v-model="form.notes" rows="2" class="input resize-none" placeholder="Catatan opsional..."></textarea>
          </div>
        </div>

        <!-- Items Section -->
        <div class="border-t border-gray-100 pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-gray-800">Daftar Item Produk</h4>
            <button @click="addItem" class="btn btn-sm btn-outline">+ Tambah Item</button>
          </div>

          <div v-if="form.items.length === 0" class="text-center py-6 text-gray-400 text-sm bg-gray-50 rounded-lg border border-dashed border-gray-200">
            Belum ada item. Klik "Tambah Item" untuk menambahkan produk.
          </div>

          <div v-else class="space-y-3">
            <div v-for="(item, idx) in form.items" :key="idx"
              class="grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg border border-gray-100">
              <div class="col-span-5">
                <label v-if="idx === 0" class="label text-xs">Produk</label>
                <select v-model="item.product_id" class="input input-sm w-full">
                  <option value="">-- Pilih --</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }}</option>
                </select>
              </div>
              <div class="col-span-2">
                <label v-if="idx === 0" class="label text-xs">Qty</label>
                <input v-model.number="item.qty" type="number" class="input input-sm text-center" min="1" />
              </div>
              <div class="col-span-2">
                <label v-if="idx === 0" class="label text-xs">Batch/Lot</label>
                <input v-model="item.batch_number" type="text" class="input input-sm" placeholder="LOT-..." />
              </div>
              <div class="col-span-2">
                <label v-if="idx === 0" class="label text-xs">Expiry</label>
                <input v-model="item.expiry_date" type="date" class="input input-sm" />
              </div>
              <div class="col-span-1 flex justify-center">
                <button @click="form.items.splice(idx, 1)" class="text-red-400 hover:text-red-600 p-1">
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showModal = false" class="btn btn-outline">Batal</button>
          <button @click="save" class="btn btn-primary" :disabled="saving || !form.warehouse_id || form.items.length === 0">
            {{ saving ? 'Menyimpan...' : 'Simpan Inbound' }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useInboundStore } from '../stores/inbound'
import { useWarehouseStore } from '../stores/warehouse'
import { useProductStore } from '../stores/product'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { useDebounce } from '../composables/useDebounce'
import { useNotificationStore } from '../stores/notification'
import { TrashIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const store = useInboundStore()
const warehouseStore = useWarehouseStore()
const productStore = useProductStore()
const notify = useNotificationStore()

const columns = [
  { key: 'inbound_number', label: 'No. Inbound', sortable: false },
  { key: 'source_type', label: 'Tipe', sortable: false },
  { key: 'source_reference', label: 'Ref. Sumber', sortable: false },
  { key: 'expected_date', label: 'Tgl. Harap', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterStatus = ref('')
const currentSearch = ref('')
const showModal = ref(false)
const saving = ref(false)
const warehouses = ref([])
const products = ref([])

const form = ref({
  warehouse_id: '',
  source_type: 'purchase_order',
  source_reference: '',
  expected_date: '',
  notes: '',
  items: []
})

function getStatusColor(status) {
  const map = {
    'pending': 'warning',
    'received': 'success',
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
  await warehouseStore.fetchList({ per_page: 100, is_active: 1 })
  warehouses.value = warehouseStore.warehouses
}

async function fetchProducts() {
  await productStore.fetchList({ per_page: 500, is_active: 1 })
  products.value = productStore.products
}

function openCreateModal() {
  form.value = {
    warehouse_id: warehouses.value.length > 0 ? warehouses.value[0].id : '',
    source_type: 'purchase_order',
    source_reference: '',
    expected_date: '',
    notes: '',
    items: []
  }
  showModal.value = true
}

function addItem() {
  form.value.items.push({
    product_id: '',
    qty: 1,
    batch_number: '',
    expiry_date: ''
  })
}

async function save() {
  if (!form.value.warehouse_id || form.value.items.length === 0) return
  // Validate items
  const validItems = form.value.items.filter(i => i.product_id && i.qty > 0)
  if (validItems.length === 0) {
    notify.error('Tambahkan minimal 1 item produk dengan kuantitas valid')
    return
  }
  saving.value = true
  try {
    await store.create({ ...form.value, items: validItems })
    showModal.value = false
    fetchData(1)
  } catch (e) {
    // handled by store
  } finally {
    saving.value = false
  }
}

async function quickReceive(row) {
  if (!confirm(`Terima seluruh item untuk Inbound ${row.inbound_number}?`)) return
  try {
    await store.receive(row.id, { notes: 'Quick receive' })
    fetchData(store.pagination.current_page)
  } catch (e) {
    // handled by store
  }
}

async function cancelInbound(row) {
  if (!confirm(`Batalkan Inbound ${row.inbound_number}?`)) return
  try {
    await store.cancel(row.id)
    fetchData(store.pagination.current_page)
  } catch (e) {
    // handled by store
  }
}

onMounted(() => {
  fetchData()
  fetchWarehouses()
  fetchProducts()
})
</script>
