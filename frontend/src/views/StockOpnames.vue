<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Stock Opname</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Stok', to: '/stock'}, {label: 'Stock Opname'}]" class="mt-1" />
      </div>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useStockOpnameStore } from '../stores/stockOpname'
import DataTable from '../components/common/DataTable.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { useDebounce } from '../composables/useDebounce'

const store = useStockOpnameStore()

const columns = [
  { key: 'opname_number', label: 'No. Dokumen', sortable: false },
  { key: 'warehouse', label: 'Gudang', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterStatus = ref('')
const currentSearch = ref('')

function getStatusColor(status) {
  const map = {
    'draft': 'inactive',
    'in_progress': 'warning',
    'submitted': 'info',
    'approved': 'success'
  }
  return map[status?.toLowerCase()] || 'inactive'
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
})
</script>
