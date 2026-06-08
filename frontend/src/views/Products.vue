<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Master Produk</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Produk'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
        + Tambah Produk
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="store.products"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari SKU, Barcode, atau Nama Produk..."
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
      @search="handleSearch"
    >
      <template #toolbar>
        <select v-model="filterCategory" class="input input-sm w-40" @change="fetchData(1)">
          <option value="">Semua Kategori</option>
          <option value="1">Raw Material</option>
          <option value="2">Packaging</option>
          <option value="3">Finished Goods</option>
        </select>
        <select v-model="filterActive" class="input input-sm w-40" @change="fetchData(1)">
          <option value="">Semua Status</option>
          <option value="1">Aktif</option>
          <option value="0">Nonaktif</option>
        </select>
      </template>

      <!-- Custom Cells -->
      <template #cell-code="{ row }">
        <div>
          <span class="font-mono text-blue-600 font-medium">{{ row.code }}</span>
          <p class="text-xs text-gray-400 font-mono mt-0.5">{{ row.sku || '-' }}</p>
        </div>
      </template>

      <template #cell-name="{ row, value }">
        <router-link :to="`/products/${row.id}`" class="font-medium text-gray-800 hover:text-blue-600 transition-colors">
          {{ value }}
        </router-link>
        <p class="text-xs text-gray-500 mt-0.5">{{ row.category?.name || 'Tanpa Kategori' }}</p>
      </template>

      <template #cell-product_type="{ value }">
        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-medium capitalize border border-slate-200">
          {{ value ? value.replace('_', ' ') : '-' }}
        </span>
      </template>

      <template #cell-is_active="{ value }">
        <StatusBadge :status="value ? 'active' : 'inactive'" :label="value ? 'Aktif' : 'Nonaktif'" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <router-link :to="`/products/${row.id}`" class="btn btn-sm btn-ghost text-blue-600">
            Detail
          </router-link>
          <button @click="openEditModal(row)" class="btn btn-sm btn-ghost text-gray-600">
            Edit
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingId ? 'Edit Produk' : 'Tambah Produk'" size="lg">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Kode Produk <span class="text-red-500">*</span></label>
          <input v-model="form.code" type="text" class="input" placeholder="PRD-001" />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">SKU</label>
          <input v-model="form.sku" type="text" class="input" placeholder="SKU-001" />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Barcode</label>
          <input v-model="form.barcode" type="text" class="input" placeholder="899..." />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Tipe Produk</label>
          <select v-model="form.product_type" class="input">
            <option value="standard">Standard</option>
            <option value="oversized">Oversized</option>
            <option value="hazmat">Hazmat</option>
            <option value="cold">Cold Storage</option>
          </select>
        </div>
        <div class="col-span-2">
          <label class="label">Nama Produk <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" class="input" placeholder="Kopi Arabica..." />
        </div>
        <div class="col-span-2">
          <label class="label">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="input resize-none" placeholder="Deskripsi..."></textarea>
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Kategori</label>
          <select v-model="form.category_id" class="input">
            <option :value="null">-- Pilih Kategori --</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Satuan Dasar (Unit)</label>
          <select v-model="form.unit_id" class="input">
            <option :value="null">-- Pilih Satuan --</option>
            <option value="1">Pcs</option>
            <option value="2">Box</option>
            <option value="3">Kg</option>
            <option value="4">Liter</option>
          </select>
        </div>
        
        <div class="col-span-2 mt-2 pt-4 border-t border-gray-100 grid grid-cols-4 gap-3">
          <div>
            <label class="label">Panjang (cm)</label>
            <input v-model="form.length_cm" type="number" class="input input-sm" />
          </div>
          <div>
            <label class="label">Lebar (cm)</label>
            <input v-model="form.width_cm" type="number" class="input input-sm" />
          </div>
          <div>
            <label class="label">Tinggi (cm)</label>
            <input v-model="form.height_cm" type="number" class="input input-sm" />
          </div>
          <div>
            <label class="label">Berat (kg)</label>
            <input v-model="form.weight_kg" type="number" class="input input-sm" />
          </div>
        </div>

        <div class="col-span-2 mt-2 pt-4 border-t border-gray-100 flex gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.track_batch" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
            <span class="text-sm font-medium text-gray-700">Track Batch/Lot</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.track_expiry" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
            <span class="text-sm font-medium text-gray-700">Track Expiry Date</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
            <span class="text-sm font-medium text-gray-700">Status Aktif</span>
          </label>
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
import { useProductStore } from '../stores/product'
import { useCategoryStore } from '../stores/category'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { useDebounce } from '../composables/useDebounce'

const store = useProductStore()
const categoryStore = useCategoryStore()

const columns = [
  { key: 'code', label: 'Kode / SKU', sortable: false },
  { key: 'name', label: 'Nama Produk', sortable: false },
  { key: 'product_type', label: 'Tipe', sortable: false },
  { key: 'is_active', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const filterActive = ref('')
const filterCategory = ref('')
const currentSearch = ref('')
const showModal = ref(false)
const editingId = ref(null)
const saving = ref(false)
const categories = ref([])

const form = ref({
  code: '', sku: '', barcode: '', name: '', description: '',
  product_type: 'standard', category_id: null, unit_id: null,
  length_cm: null, width_cm: null, height_cm: null, weight_kg: null,
  track_batch: false, track_expiry: false, is_active: true
})

function fetchData(page = 1) {
  const params = { page, per_page: 25 }
  if (filterActive.value !== '') params.is_active = filterActive.value
  if (filterCategory.value !== '') params.category_id = filterCategory.value
  if (currentSearch.value) params.search = currentSearch.value
  store.fetchList(params)
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
  editingId.value = null
  form.value = {
    code: '', sku: '', barcode: '', name: '', description: '',
    product_type: 'standard', category_id: null, unit_id: null,
    length_cm: null, width_cm: null, height_cm: null, weight_kg: null,
    track_batch: false, track_expiry: false, is_active: true
  }
  showModal.value = true
}

function openEditModal(row) {
  editingId.value = row.id
  form.value = {
    code: row.code || '',
    sku: row.sku || '',
    barcode: row.barcode || '',
    name: row.name || '',
    description: row.description || '',
    product_type: row.product_type || 'standard',
    category_id: row.category_id || null,
    unit_id: row.unit_id || null,
    length_cm: row.length_cm || null,
    width_cm: row.width_cm || null,
    height_cm: row.height_cm || null,
    weight_kg: row.weight_kg || null,
    track_batch: !!row.track_batch,
    track_expiry: !!row.track_expiry,
    is_active: row.is_active === undefined ? true : !!row.is_active
  }
  showModal.value = true
}

async function save() {
  if (!form.value.code || !form.value.name) return
  try {
    saving.value = true
    const payload = { ...form.value }
    console.log('Sending payload:', payload);
    if (editingId.value) {
      await store.update(editingId.value, payload)
    } else {
      await store.create(payload)
    }
    showModal.value = false
    fetchData(store.pagination?.current_page || 1)
  } catch (e) {
    // Error handled by store
  } finally {
    saving.value = false
  }
}

async function fetchCategories() {
  await categoryStore.fetchList()
  categories.value = categoryStore.categories
}

onMounted(() => {
  fetchCategories()
  fetchData()
})
</script>