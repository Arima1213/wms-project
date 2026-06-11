<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold">Retur Barang</h1>
        <p class="text-sm text-gray-500">Kelola retur barang masuk dari customer/supplier</p>
      </div>
      <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        + Retur Baru
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-3 mb-4 flex-wrap">
      <select v-model="filters.status" @change="fetchData" class="px-3 py-2 border rounded-lg text-sm">
        <option value="">Semua Status</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending</option>
        <option value="approved">Disetujui</option>
        <option value="rejected">Ditolak</option>
        <option value="processed">Diproses</option>
        <option value="cancelled">Dibatalkan</option>
      </select>
      <select v-model="filters.type" @change="fetchData" class="px-3 py-2 border rounded-lg text-sm">
        <option value="">Semua Tipe</option>
        <option value="customer_return">Retur Customer</option>
        <option value="supplier_return">Retur Supplier</option>
        <option value="internal">Internal</option>
      </select>
    </div>

    <!-- Table -->
    <DataTable :loading="store.loading" :columns="columns" :rows="store.items" @row-click="viewDetail">
      <template #status="{ row }">
        <StatusBadge :status="row.status" />
      </template>
      <template #type="{ row }">
        <span class="text-sm">{{ typeLabel(row.type) }}</span>
      </template>
      <template #actions="{ row }">
        <div class="flex gap-1">
          <button @click.stop="viewDetail(row)" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded" title="Detail">Detail</button>
          <button v-if="row.status === 'draft'" @click.stop="openEdit(row)" class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">Edit</button>
          <button v-if="row.status === 'pending'" @click.stop="confirmAction(row.id, 'approve')" class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Setuju</button>
          <button v-if="row.status === 'pending'" @click.stop="confirmAction(row.id, 'reject')" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded">Tolak</button>
          <button v-if="row.status === 'approved'" @click.stop="confirmAction(row.id, 'process')" class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded">Proses</button>
          <button v-if="['draft', 'pending', 'approved'].includes(row.status)" @click.stop="confirmAction(row.id, 'cancel')" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">Batal</button>
        </div>
      </template>
    </DataTable>

    <!-- Pagination -->
    <Pagination v-if="store.pagination.lastPage > 1" v-model="store.pagination.currentPage" :last-page="store.pagination.lastPage" @change="fetchData" />

    <!-- Create Modal -->
    <Modal v-if="showCreateModal" title="Retur Baru" @close="showCreateModal = false">
      <ReturnForm @saved="onSaved" @cancel="showCreateModal = false" />
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useReturnStore } from '../stores/returns'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import Pagination from '../components/common/Pagination.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import ReturnForm from '../components/returns/ReturnForm.vue'

const router = useRouter()
const store = useReturnStore()
const showCreateModal = ref(false)

const columns = [
  { key: 'return_number', label: 'No. Retur' },
  { key: 'type', label: 'Tipe', slot: 'type' },
  { key: 'status', label: 'Status', slot: 'status' },
  { key: 'warehouse.name', label: 'Gudang' },
  { key: 'return_date', label: 'Tanggal' },
  { key: 'refund_amount', label: 'Nilai Refund' },
  { key: 'actions', label: 'Aksi', slot: 'actions' },
]

const filters = reactive({
  status: '',
  type: '',
})

async function fetchData() {
  const params = { ...filters }
  params.page = store.pagination.currentPage
  await store.fetchList(params)
}

function viewDetail(row) {
  router.push(`/returns/${row.id}`)
}

function typeLabel(type) {
  const labels = {
    customer_return: 'Retur Customer',
    supplier_return: 'Retur Supplier',
    internal: 'Internal',
  }
  return labels[type] || type
}

function openEdit(row) {
  router.push(`/returns/${row.id}?edit=1`)
}

async function confirmAction(id, action) {
  if (!confirm(`Yakin ingin ${actionLabel(action)} retur ini?`)) return
  try {
    const actions = {
      approve: () => store.approve(id),
      reject: () => store.reject(id),
      process: () => store.process(id),
      cancel: () => store.cancel(id),
    }
    const res = await actions[action]()
    alert(res.message)
    await fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal memproses retur')
  }
}

function actionLabel(action) {
  return { approve: 'menyetujui', reject: 'menolak', process: 'memproses', cancel: 'membatalkan' }[action]
}

function onSaved() {
  showCreateModal.value = false
  fetchData()
}

onMounted(fetchData)
</script>
