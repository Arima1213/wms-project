<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Audit Log</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Audit Log'}]" class="mt-1" />
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="label">Entity Type</label>
          <select v-model="filters.entity_type" class="input input-sm" @change="fetchData(1)">
            <option value="">Semua</option>
            <option value="product">Product</option>
            <option value="inbound">Inbound</option>
            <option value="outbound">Outbound</option>
            <option value="transfer">Transfer</option>
            <option value="stock_opname">Stock Opname</option>
            <option value="user">User</option>
            <option value="setting">Setting</option>
          </select>
        </div>
        <div>
          <label class="label">Dari Tanggal</label>
          <input v-model="filters.from" type="date" class="input input-sm" @change="fetchData(1)" />
        </div>
        <div>
          <label class="label">Sampai Tanggal</label>
          <input v-model="filters.to" type="date" class="input input-sm" @change="fetchData(1)" />
        </div>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :data="store.items"
      :loading="store.loading"
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
    >
      <template #cell-created_at="{ value }">
        <span class="text-sm font-mono">{{ formatDate(value) }}</span>
      </template>

      <template #cell-user="{ value }">
        <span class="text-sm">{{ value?.name || 'System' }}</span>
      </template>

      <template #cell-action="{ value }">
        <StatusBadge :status="actionColor(value)" :label="value" />
      </template>

      <template #cell-entity_type="{ value }">
        <span class="text-sm capitalize">{{ value || '-' }}</span>
      </template>

      <template #cell-entity_id="{ value }">
        <span class="text-sm font-mono">{{ value || '-' }}</span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuditLogStore } from '../stores/auditLog'
import DataTable from '../components/common/DataTable.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'

const store = useAuditLogStore()

const columns = [
  { key: 'created_at', label: 'Waktu', sortable: false },
  { key: 'user', label: 'User', sortable: false },
  { key: 'action', label: 'Aksi', sortable: false },
  { key: 'entity_type', label: 'Tipe Entitas', sortable: false },
  { key: 'entity_id', label: 'ID Entitas', sortable: false },
]

const filters = reactive({ entity_type: '', from: '', to: '' })

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString('id-ID')
}

function actionColor(action) {
  const map = {
    created: 'info', updated: 'warning', deleted: 'danger',
    login: 'success', logout: 'inactive', viewed: 'primary'
  }
  return map[action?.toLowerCase()] || 'inactive'
}

async function fetchData(page = 1) {
  const params = { page }
  if (filters.entity_type) params.entity_type = filters.entity_type
  if (filters.from) params.from = filters.from
  if (filters.to) params.to = filters.to
  await store.fetchList(params)
}

function handlePageChange(page) {
  fetchData(page)
}

onMounted(() => fetchData())
</script>
