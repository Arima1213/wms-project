<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Dokumen</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Dokumen'}]" class="mt-1" />
      </div>
      <button @click="openUploadModal" class="btn btn-sm btn-primary">+ Upload Dokumen</button>
    </div>

    <DataTable
      :columns="columns"
      :data="store.items"
      :loading="store.loading"
      :paginated="true"
      :pagination="store.pagination"
      @page-change="handlePageChange"
    >
      <template #cell-size="{ value }">
        <span class="font-mono text-sm">{{ formatBytes(value) }}</span>
      </template>

      <template #cell-user="{ row }">
        <span class="text-sm">{{ row.user?.name || '-' }}</span>
      </template>

      <template #cell-created_at="{ value }">
        <span class="text-sm">{{ formatDate(value) }}</span>
      </template>

      <template #cell-actions="{ row }">
        <button @click="confirmDelete(row)" class="btn btn-sm btn-ghost text-red-500">Hapus</button>
      </template>
    </DataTable>

    <!-- Upload Modal -->
    <Modal v-model="showModal" title="Upload Dokumen" size="md">
      <div class="space-y-4">
        <div>
          <label class="label">Nama Dokumen <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" class="input" placeholder="Nama dokumen" />
        </div>
        <div>
          <label class="label">Tipe Dokumen <span class="text-red-500">*</span></label>
          <select v-model="form.type" class="input">
            <option value="">-- Pilih Tipe --</option>
            <option value="invoice">Invoice</option>
            <option value="delivery_note">Delivery Note</option>
            <option value="packing_list">Packing List</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="label">File <span class="text-red-500">*</span></label>
          <input ref="fileInput" type="file" class="input" accept=".pdf,.jpg,.png,.xlsx,.docx" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showModal = false" class="btn btn-outline">Batal</button>
          <button @click="handleUpload" class="btn btn-primary" :disabled="uploading">
            {{ uploading ? 'Mengupload...' : 'Upload' }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Delete Confirmation -->
    <Modal v-model="showDeleteModal" title="Hapus Dokumen" size="sm">
      <p class="text-gray-600">Yakin ingin menghapus <strong>{{ deleteTarget?.name }}</strong>?</p>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showDeleteModal = false" class="btn btn-outline">Batal</button>
          <button @click="doDelete" class="btn btn-sm bg-red-600 text-white hover:bg-red-700">Hapus</button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useDocumentStore } from '../stores/document'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'

const store = useDocumentStore()
const fileInput = ref(null)
const showModal = ref(false)
const showDeleteModal = ref(false)
const uploading = ref(false)
const deleteTarget = ref(null)

const form = reactive({ name: '', type: '' })

const columns = [
  { key: 'name', label: 'Nama', sortable: false },
  { key: 'type', label: 'Tipe', sortable: false },
  { key: 'size', label: 'Ukuran', sortable: false },
  { key: 'user', label: 'Upload oleh', sortable: false },
  { key: 'created_at', label: 'Tanggal', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0, size = bytes
  while (size >= 1024 && i < units.length - 1) { size /= 1024; i++ }
  return `${size.toFixed(i === 0 ? 0 : 1)} ${units[i]}`
}

function formatDate(v) {
  if (!v) return '-'
  return new Date(v).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}

function openUploadModal() { form.name = ''; form.type = ''; showModal.value = true }

async function handleUpload() {
  if (!form.name || !form.type || !fileInput.value?.files[0]) return
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('file', fileInput.value.files[0])
    fd.append('name', form.name)
    fd.append('type', form.type)
    await store.upload(fd)
    showModal.value = false
    await store.fetchList()
  } catch (e) {
    // store handles error
  } finally {
    uploading.value = false
  }
}

function confirmDelete(row) { deleteTarget.value = row; showDeleteModal.value = true }

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await store.remove(deleteTarget.value.id)
    showDeleteModal.value = false
    deleteTarget.value = null
    await store.fetchList()
  } catch (e) {
    // store handles error
  }
}

function handlePageChange(page) { store.fetchList({ page }) }

onMounted(() => store.fetchList())
</script>
