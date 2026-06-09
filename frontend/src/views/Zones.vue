<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Zona</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Zona'}]" class="mt-1" />
      </div>
      <div class="flex items-center gap-3">
        <select v-model="selectedWarehouse" class="input input-sm w-56">
          <option value="">-- Pilih Gudang --</option>
          <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
        </select>
        <button v-if="selectedWarehouse" @click="openCreateModal" class="btn btn-sm btn-primary">+ Tambah Zona</button>
      </div>
    </div>

    <DataTable
      :columns="columns"
      :data="store.zones"
      :loading="store.loading"
    >
      <template #cell-code="{ value }">
        <code class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono">{{ value }}</code>
      </template>

      <template #cell-is_active="{ value }">
        <span class="font-medium text-sm" :class="value ? 'text-emerald-600' : 'text-red-400'">
          {{ value ? 'Aktif' : 'Nonaktif' }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <button @click="openEditModal(row)" class="btn btn-sm btn-ghost text-blue-600">Edit</button>
          <button @click="toggleActive(row)" class="btn btn-sm btn-ghost" :class="row.is_active ? 'text-amber-500' : 'text-emerald-600'">
            {{ row.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
          </button>
          <button @click="deleteZone(row)" class="btn btn-sm btn-ghost text-red-500">Hapus</button>
        </div>
      </template>
    </DataTable>

    <!-- No warehouse selected -->
    <div v-if="!selectedWarehouse" class="text-center py-16 text-gray-400">
      <p class="text-lg">Pilih gudang terlebih dahulu</p>
    </div>

    <!-- Modal -->
    <Modal v-model="showModal" :title="isEditing ? 'Edit Zona' : 'Tambah Zona'" size="sm">
      <div class="space-y-4">
        <div>
          <label class="label">Kode Zona <span class="text-red-500">*</span></label>
          <input v-model="form.code" type="text" class="input" placeholder="Z-01" />
        </div>
        <div>
          <label class="label">Nama Zona</label>
          <input v-model="form.name" type="text" class="input" placeholder="Nama zona" />
        </div>
        <div>
          <label class="label">Deskripsi</label>
          <textarea v-model="form.description" class="input" rows="2" placeholder="Opsional"></textarea>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showModal = false" class="btn btn-outline">Batal</button>
          <button @click="saveZone" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan' : 'Tambah') }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useZoneStore } from '../stores/zone'
import { useWarehouseStore } from '../stores/warehouse'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'

const store = useZoneStore()
const warehouseStore = useWarehouseStore()

const selectedWarehouse = ref('')
const warehouses = ref([])
const showModal = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const editingId = ref(null)
const form = reactive({ code: '', name: '', description: '' })

const columns = [
  { key: 'code', label: 'Kode', sortable: false },
  { key: 'name', label: 'Nama Zona', sortable: false },
  { key: 'description', label: 'Deskripsi', sortable: false },
  { key: 'is_active', label: 'Status', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

function resetForm() { form.code = ''; form.name = ''; form.description = '' }

function openCreateModal() { isEditing.value = false; resetForm(); showModal.value = true }

function openEditModal(zone) {
  isEditing.value = true; editingId.value = zone.id
  form.code = zone.code || ''; form.name = zone.name || ''; form.description = zone.description || ''
  showModal.value = true
}

async function saveZone() {
  if (!form.code.trim()) return
  submitting.value = true
  try {
    const payload = { code: form.code.trim(), name: form.name.trim() || '', description: form.description.trim() || undefined }
    if (isEditing.value) await store.update(selectedWarehouse.value, editingId.value, payload)
    else await store.create(selectedWarehouse.value, payload)
    showModal.value = false
    await store.fetchList(selectedWarehouse.value)
  } catch (e) { /* store handles */ } finally { submitting.value = false }
}

async function deleteZone(zone) {
  if (!confirm(`Hapus zona "${zone.name || zone.code}"?`)) return
  try {
    await store.remove(selectedWarehouse.value, zone.id)
    await store.fetchList(selectedWarehouse.value)
  } catch (e) { /* store handles */ }
}

async function toggleActive(zone) {
  try {
    await store.toggleActive(zone.id, !zone.is_active)
    await store.fetchList(selectedWarehouse.value)
  } catch (e) { /* store handles */ }
}

// Gunakan watch, bukan @change, untuk hindari masalah timing
watch(selectedWarehouse, (newVal) => {
  if (newVal) {
    console.log('[Zones] Selected warehouse:', newVal)
    store.fetchList(newVal)
  } else {
    store.zones = []
  }
})

onMounted(async () => {
  await warehouseStore.fetchList({ per_page: 100 })
  warehouses.value = warehouseStore.warehouses || []
  console.log('[Zones] Warehouses loaded:', warehouses.value.length)
})
</script>
