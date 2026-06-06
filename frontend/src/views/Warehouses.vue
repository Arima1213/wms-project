<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Gudang</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Gudang'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        + Tambah Gudang
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="store.warehouses"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari gudang..."
      :search-keys="['code', 'name', 'city']"
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
    >
      <template #toolbar>
        <select v-model="filterActive" class="input input-sm w-40" @change="fetchData">
          <option value="">Semua Status</option>
          <option value="1">Aktif</option>
          <option value="0">Nonaktif</option>
        </select>
      </template>

      <!-- Custom Cells -->
      <template #cell-code="{ value }">
        <span class="font-mono text-blue-600 font-medium">{{ value }}</span>
      </template>

      <template #cell-name="{ row, value }">
        <router-link :to="`/warehouses/${row.id}`" class="font-medium text-gray-800 hover:text-blue-600 transition-colors">
          {{ value }}
        </router-link>
      </template>

      <template #cell-warehouse_type="{ value }">
        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-medium capitalize border border-slate-200">
          {{ value ? value.replace('_', ' ') : 'Reguler' }}
        </span>
      </template>

      <template #cell-is_active="{ value }">
        <StatusBadge :status="value ? 'active' : 'inactive'" :label="value ? 'Aktif' : 'Nonaktif'" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <router-link :to="`/warehouses/${row.id}`" class="btn btn-sm btn-ghost text-blue-600">
            Detail
          </router-link>
          <router-link :to="`/planograms/${row.id}`" class="btn btn-sm btn-ghost text-indigo-600">
            Planogram
          </router-link>
          <button @click="openEditModal(row)" class="btn btn-sm btn-ghost text-gray-600">
            Edit
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingId ? 'Edit Gudang' : 'Tambah Gudang'" size="lg">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Kode Gudang <span class="text-red-500">*</span></label>
          <input v-model="form.code" type="text" class="input" placeholder="WH001" />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Tipe Gudang</label>
          <select v-model="form.warehouse_type" class="input">
            <option value="reguler">Reguler</option>
            <option value="cold_storage">Cold Storage</option>
            <option value="bonded">Bonded</option>
            <option value="konsinyasi">Konsinyasi</option>
          </select>
        </div>
        <div class="col-span-2">
          <label class="label">Nama Gudang <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" class="input" placeholder="Gudang Utama" />
        </div>
        <div class="col-span-2">
          <label class="label">Alamat Lengkap</label>
          <textarea v-model="form.address" rows="2" class="input resize-none" placeholder="Alamat..."></textarea>
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Kota</label>
          <input v-model="form.city" type="text" class="input" placeholder="Jakarta" />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Kapasitas (m²)</label>
          <input v-model="form.capacity_m2" type="number" class="input" placeholder="1000" />
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
import { useWarehouseStore } from '../stores/warehouse'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'

const store = useWarehouseStore()

const columns = [
  { key: 'code', label: 'Kode', sortable: true },
  { key: 'name', label: 'Nama Gudang', sortable: true },
  { key: 'warehouse_type', label: 'Tipe', sortable: true },
  { key: 'city', label: 'Kota', sortable: true },
  { key: 'is_active', label: 'Status', sortable: true },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterActive = ref('')
const showModal = ref(false)
const editingId = ref(null)
const saving = ref(false)

const form = ref({
  code: '', name: '', warehouse_type: 'reguler',
  address: '', city: '', capacity_m2: null
})

function fetchData(page = 1) {
  const params = { page, per_page: 25 }
  if (filterActive.value !== '') params.is_active = filterActive.value
  store.fetchList(params)
}

function handlePageChange(page) {
  fetchData(page)
}

function openCreateModal() {
  editingId.value = null
  form.value = { code: '', name: '', warehouse_type: 'reguler', address: '', city: '', capacity_m2: null }
  showModal.value = true
}

function openEditModal(row) {
  editingId.value = row.id
  form.value = {
    code: row.code,
    name: row.name,
    warehouse_type: row.warehouse_type || 'reguler',
    address: row.address || '',
    city: row.city || '',
    capacity_m2: row.capacity_m2 || null
  }
  showModal.value = true
}

async function save() {
  if (!form.value.code || !form.value.name) return
  saving.value = true
  try {
    const payload = { ...form.value }
    if (editingId.value) {
      await store.update(editingId.value, payload)
    } else {
      await store.create(payload)
    }
    showModal.value = false
    fetchData(store.pagination?.current_page || 1)
  } catch (e) {
    // error handled by store/notification
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>