<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Kategori Produk</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Kategori'}]" class="mt-1" />
      </div>
      <button @click="openCreateModal" class="btn btn-sm btn-primary" :disabled="store.loading">
        + Tambah Kategori
      </button>
    </div>

    <DataTable
      :columns="columns"
      :data="store.categories"
      :loading="store.loading"
      :searchable="true"
      search-placeholder="Cari kategori..."
      @search="handleSearch"
    >
      <template #cell-code="{ value }">
        <code v-if="value" class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono">{{ value }}</code>
        <span v-else class="text-gray-300">&mdash;</span>
      </template>

      <template #cell-is_active="{ value }">
        <span class="font-medium text-sm" :class="value ? 'text-emerald-600' : 'text-red-400'">
          {{ value ? 'Aktif' : 'Nonaktif' }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <button @click="openEditModal(row)" class="btn btn-sm btn-ghost text-blue-600">Edit</button>
          <button @click="deleteCategory(row)" class="btn btn-sm btn-ghost text-red-500">Hapus</button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showModal" :title="isEditing ? 'Edit Kategori' : 'Tambah Kategori Baru'" size="sm">
      <div class="space-y-4">
        <div>
          <label class="label">Nama Kategori <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" class="input" placeholder="Nama kategori" />
        </div>
        <div>
          <label class="label">Kode Kategori</label>
          <input v-model="form.code" type="text" class="input" placeholder="CONTOH-01" />
        </div>
        <div>
          <label class="label">Deskripsi</label>
          <textarea v-model="form.description" class="input" rows="3" placeholder="Deskripsi opsional..."></textarea>
        </div>
        <div>
          <label class="label">Status</label>
          <select v-model="form.is_active" class="input">
            <option :value="true">Aktif</option>
            <option :value="false">Nonaktif</option>
          </select>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showModal = false" class="btn btn-outline">Batal</button>
          <button @click="saveCategory" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Kategori') }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useCategoryStore } from '../stores/category'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { useDebounce } from '../composables/useDebounce'

const store = useCategoryStore()

const columns = [
  { key: 'name', label: 'Nama Kategori', sortable: true },
  { key: 'code', label: 'Kode', sortable: false },
  { key: 'description', label: 'Deskripsi', sortable: false },
  { key: 'is_active', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const showModal = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const editingId = ref(null)

const form = reactive({
  name: '',
  code: '',
  description: '',
  is_active: true,
})

function resetForm() {
  form.name = ''
  form.code = ''
  form.description = ''
  form.is_active = true
}

function openCreateModal() {
  isEditing.value = false
  resetForm()
  showModal.value = true
}

function openEditModal(category) {
  isEditing.value = true
  editingId.value = category.id
  form.name = category.name || ''
  form.code = category.code || ''
  form.description = category.description || ''
  form.is_active = category.is_active ?? true
  showModal.value = true
}

async function saveCategory() {
  if (!form.name.trim()) {
    return
  }
  submitting.value = true
  try {
    const payload = {
      name: form.name.trim(),
      code: form.code.trim() || undefined,
      description: form.description.trim() || undefined,
      is_active: form.is_active,
    }
    if (isEditing.value) {
      await store.update(editingId.value, payload)
    } else {
      await store.create(payload)
    }
    showModal.value = false
    await store.fetchList()
  } catch (e) {
    // Store handles error notification
  } finally {
    submitting.value = false
  }
}

async function deleteCategory(category) {
  if (!confirm(`Yakin ingin menghapus kategori "${category.name}"?`)) return
  try {
    await store.remove(category.id)
    await store.fetchList()
  } catch (e) {
    // Store handles error notification
  }
}

function handleSearch(query) {
  store.fetchList({ search: query || undefined })
}

onMounted(() => {
  store.fetchList()
})
</script>
