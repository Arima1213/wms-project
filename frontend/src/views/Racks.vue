<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Rak & Slot</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Rak & Slot'}]" class="mt-1" />
      </div>
    </div>

    <!-- Filters Row -->
    <div class="card p-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Gudang</label>
          <select v-model="selectedWarehouse" class="input" @change="onWarehouseChange">
            <option value="">-- Pilih Gudang --</option>
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Zona</label>
          <select v-model="selectedZone" class="input" :disabled="!selectedWarehouse" @change="onZoneChange">
            <option value="">-- Pilih Zona --</option>
            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.code }} - {{ z.name }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Racks Section -->
    <div v-if="selectedZone">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-700">Daftar Rak</h3>
        <button @click="openRackModal()" class="btn btn-sm btn-primary">+ Tambah Rak</button>
      </div>

      <DataTable :columns="rackColumns" :data="rackStore.racks" :loading="rackStore.loading">
        <template #cell-code="{ value }">
          <code class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono">{{ value }}</code>
        </template>

        <template #cell-slots_count="{ value }">
          <span class="text-sm font-medium">{{ value || 0 }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2">
            <button @click="selectedRack = row; slotStore.fetchList({ rack_id: row.id })" class="btn btn-sm btn-ghost text-indigo-600"
              :class="{'font-bold': selectedRack?.id === row.id}">Slot</button>
            <button @click="openRackModal(row)" class="btn btn-sm btn-ghost text-blue-600">Edit</button>
            <button @click="deleteRack(row)" class="btn btn-sm btn-ghost text-red-500">Hapus</button>
          </div>
        </template>
      </DataTable>

      <!-- Slots Section -->
      <div v-if="selectedRack" class="mt-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-700">
            Slot: {{ selectedRack.code }}
            <span class="text-sm font-normal text-gray-500 ml-2">({{ slotStore.slots.length }} slot)</span>
          </h3>
          <button @click="openSlotModal()" class="btn btn-sm btn-outline">+ Tambah Slot</button>
        </div>

        <DataTable :columns="slotColumns" :data="slotStore.slots" :loading="slotStore.loading">
          <template #cell-slot_code="{ value }">
            <code class="bg-blue-50 px-2 py-0.5 rounded text-xs font-mono text-blue-700">{{ value }}</code>
          </template>

          <template #cell-status="{ value }">
            <StatusBadge :status="slotStatusColor(value)" :label="value || 'empty'" />
          </template>

          <template #cell-product="{ row }">
            <span class="text-sm">{{ row.product?.name || '-' }}</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex items-center gap-1">
              <button v-if="!row.product_id" @click="openAssignModal(row)" class="btn btn-sm btn-ghost text-emerald-600">Assign</button>
              <button v-if="row.product_id" @click="unassignSlot(row)" class="btn btn-sm btn-ghost text-amber-600">Lepas</button>
              <button @click="openSlotModal(row)" class="btn btn-sm btn-ghost text-blue-600">Edit</button>
              <button @click="deleteSlot(row)" class="btn btn-sm btn-ghost text-red-500">Hapus</button>
            </div>
          </template>
        </DataTable>

        <div v-if="!selectedRack" class="text-center py-8 text-gray-400">
          Pilih rak untuk melihat slot
        </div>
      </div>
    </div>

    <div v-if="selectedWarehouse && !selectedZone" class="text-center py-16 text-gray-400">
      <p class="text-lg">Pilih zona untuk melihat rak</p>
    </div>

    <div v-if="!selectedWarehouse" class="text-center py-16 text-gray-400">
      <p class="text-lg">Pilih gudang terlebih dahulu</p>
    </div>

    <!-- Rack Modal -->
    <Modal v-model="showRackModal" :title="isEditingRack ? 'Edit Rak' : 'Tambah Rak'" size="sm">
      <div class="space-y-4">
        <div>
          <label class="label">Kode Rak <span class="text-red-500">*</span></label>
          <input v-model="rackForm.code" type="text" class="input" placeholder="R-A1" />
        </div>
        <div>
          <label class="label">Nama Rak</label>
          <input v-model="rackForm.name" type="text" class="input" placeholder="Rak A1" />
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="label">Pos X</label>
            <input v-model.number="rackForm.pos_x" type="number" class="input" placeholder="0" />
          </div>
          <div>
            <label class="label">Pos Y</label>
            <input v-model.number="rackForm.pos_y" type="number" class="input" placeholder="0" />
          </div>
          <div>
            <label class="label">Width</label>
            <input v-model.number="rackForm.width" type="number" class="input" placeholder="0" />
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showRackModal = false" class="btn btn-outline">Batal</button>
          <button @click="saveRack" class="btn btn-primary" :disabled="submittingRack">
            {{ submittingRack ? 'Menyimpan...' : (isEditingRack ? 'Simpan' : 'Tambah') }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Slot Modal -->
    <Modal v-model="showSlotModal" :title="isEditingSlot ? 'Edit Slot' : 'Tambah Slot'" size="sm">
      <div class="space-y-4">
        <div>
          <label class="label">Kode Slot <span class="text-red-500">*</span></label>
          <input v-model="slotForm.slot_code" type="text" class="input" placeholder="A1-01" />
        </div>
        <div>
          <label class="label">Nomor Slot</label>
          <input v-model.number="slotForm.slot_number" type="number" class="input" placeholder="1" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Max Berat (kg)</label>
            <input v-model.number="slotForm.max_weight_kg" type="number" class="input" placeholder="50" />
          </div>
          <div>
            <label class="label">Max Tinggi (cm)</label>
            <input v-model.number="slotForm.max_height_cm" type="number" class="input" placeholder="30" />
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showSlotModal = false" class="btn btn-outline">Batal</button>
          <button @click="saveSlot" class="btn btn-primary" :disabled="submittingSlot">
            {{ submittingSlot ? 'Menyimpan...' : (isEditingSlot ? 'Simpan' : 'Tambah') }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Assign Product Modal -->
    <Modal v-model="showAssignModal" title="Assign Produk ke Slot" size="sm">
      <div class="space-y-4">
        <div>
          <label class="label">Produk</label>
          <input v-model="assignQuery" type="text" class="input" placeholder="Cari produk..." @input="searchProducts" />
          <ul v-if="productResults.length" class="mt-2 border rounded-lg divide-y max-h-40 overflow-auto">
            <li v-for="p in productResults" :key="p.id"
              class="px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm"
              :class="{'bg-blue-100': selectedProduct?.id === p.id}"
              @click="selectProduct(p)">
              {{ p.name }} ({{ p.sku || p.code }})
            </li>
          </ul>
        </div>
        <div v-if="selectedProduct">
          <label class="label">Produk Dipilih</label>
          <div class="input bg-gray-50">{{ selectedProduct.name }}</div>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button @click="showAssignModal = false" class="btn btn-outline">Batal</button>
          <button @click="assignProduct" class="btn btn-primary" :disabled="!selectedProduct || assigning">
            {{ assigning ? 'Menyimpan...' : 'Assign' }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRackStore } from '../stores/rack'
import { useRackSlotStore } from '../stores/rackSlot'
import { useWarehouseStore } from '../stores/warehouse'
import { zoneAPI, rackAPI, rackSlotAPI } from '../services/api'
import { productAPI } from '../services/api'
import DataTable from '../components/common/DataTable.vue'
import Modal from '../components/common/Modal.vue'
import StatusBadge from '../components/common/StatusBadge.vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'

const rackStore = useRackStore()
const slotStore = useRackSlotStore()
const warehouseStore = useWarehouseStore()

const warehouses = ref([])
const zones = ref([])
const selectedWarehouse = ref('')
const selectedZone = ref('')
const selectedRack = ref(null)

// Rack CRUD
const showRackModal = ref(false)
const isEditingRack = ref(false)
const submittingRack = ref(false)
const editingRackId = ref(null)
const rackForm = reactive({ code: '', name: '', pos_x: 0, pos_y: 0, width: 0 })

// Slot CRUD
const showSlotModal = ref(false)
const isEditingSlot = ref(false)
const submittingSlot = ref(false)
const editingSlotId = ref(null)
const slotForm = reactive({ slot_code: '', slot_number: 1, max_weight_kg: null, max_height_cm: null })

// Assign
const showAssignModal = ref(false)
const assigning = ref(false)
const assignTarget = ref(null)
const assignQuery = ref('')
const selectedProduct = ref(null)
const productResults = ref([])

const rackColumns = [
  { key: 'code', label: 'Kode', sortable: false },
  { key: 'name', label: 'Nama', sortable: false },
  { key: 'slots_count', label: 'Jumlah Slot', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

const slotColumns = [
  { key: 'slot_code', label: 'Kode Slot', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'product', label: 'Produk', sortable: false },
  { key: 'actions', label: 'Aksi', sortable: false, headerClass: 'text-right', cellClass: 'text-right' },
]

function slotStatusColor(s) {
  const map = { occupied: 'info', reserved: 'warning', damaged: 'danger', empty: 'inactive' }
  return map[s?.toLowerCase()] || 'inactive'
}

// Warehouse/Zone loading
async function onWarehouseChange() {
  selectedZone.value = ''
  selectedRack.value = null
  zones.value = []
  rackStore.racks = []
  slotStore.slots = []
  if (!selectedWarehouse.value) return
  try {
    const res = await zoneAPI.list(selectedWarehouse.value)
    zones.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) { zones.value = [] }
}

function onZoneChange() {
  selectedRack.value = null
  slotStore.slots = []
  if (selectedZone.value) rackStore.fetchList(selectedZone.value)
  else rackStore.racks = []
}

// Rack CRUD
function resetRackForm() { rackForm.code = ''; rackForm.name = ''; rackForm.pos_x = 0; rackForm.pos_y = 0; rackForm.width = 0 }

function openRackModal(rack) {
  if (rack) {
    isEditingRack.value = true; editingRackId.value = rack.id
    rackForm.code = rack.code || ''; rackForm.name = rack.name || ''
    rackForm.pos_x = rack.pos_x || 0; rackForm.pos_y = rack.pos_y || 0; rackForm.width = rack.width || 0
  } else { isEditingRack.value = false; resetRackForm() }
  showRackModal.value = true
}

async function saveRack() {
  if (!rackForm.code.trim()) return
  submittingRack.value = true
  try {
    const p = { code: rackForm.code.trim(), name: rackForm.name.trim() || undefined, pos_x: rackForm.pos_x || undefined, pos_y: rackForm.pos_y || undefined, width: rackForm.width || undefined }
    if (isEditingRack.value) {
      await rackStore.update(selectedZone.value, editingRackId.value, p)
    } else {
      const res = await rackAPI.create(selectedZone.value, p)
      // Auto-select the newly created rack so user can add slots immediately
      const newRack = res.data || res
      selectedRack.value = newRack
    }
    showRackModal.value = false
    await rackStore.fetchList(selectedZone.value)
  } catch (e) { /* store handles */ } finally { submittingRack.value = false }
}

async function deleteRack(rack) {
  if (!confirm(`Hapus rak "${rack.code}"?`)) return
  try {
    await rackStore.remove(selectedZone.value, rack.id)
    if (selectedRack.value?.id === rack.id) selectedRack.value = null
    await rackStore.fetchList(selectedZone.value)
  } catch (e) { /* store handles */ }
}

// Slot CRUD
function resetSlotForm() { slotForm.slot_code = ''; slotForm.slot_number = 1; slotForm.max_weight_kg = null; slotForm.max_height_cm = null }

function openSlotModal(slot) {
  if (slot) {
    isEditingSlot.value = true; editingSlotId.value = slot.id
    slotForm.slot_code = slot.slot_code || ''; slotForm.slot_number = slot.slot_number || 1
    slotForm.max_weight_kg = slot.max_weight_kg || null; slotForm.max_height_cm = slot.max_height_cm || null
  } else { isEditingSlot.value = false; resetSlotForm() }
  showSlotModal.value = true
}

async function saveSlot() {
  if (!slotForm.slot_code.trim()) return
  submittingSlot.value = true
  try {
    // Ensure rack levels are loaded
    if (!selectedRack.value?.levels?.length) {
      const rackDetail = await rackAPI.show(selectedZone.value, selectedRack.value.id)
      selectedRack.value = rackDetail.data || rackDetail
    }
    const levelId = selectedRack.value?.levels?.[0]?.id
    if (!levelId) throw new Error('Rak belum memiliki level, buat level terlebih dahulu')

    const p = { slot_code: slotForm.slot_code.trim(), slot_number: slotForm.slot_number || 1, rack_level_id: levelId, max_weight_kg: slotForm.max_weight_kg || undefined, max_height_cm: slotForm.max_height_cm || undefined }
    if (isEditingSlot.value) await slotStore.update(editingSlotId.value, p)
    else await slotStore.create(p)
    showSlotModal.value = false; await slotStore.fetchList({ rack_id: selectedRack.value.id })
  } catch (e) { /* store handles */ } finally { submittingSlot.value = false }
}

async function deleteSlot(slot) {
  if (!confirm(`Hapus slot "${slot.slot_code}"?`)) return
  if (!selectedRack.value) return
  try { await slotStore.remove(slot.id); await slotStore.fetchList({ rack_id: selectedRack.value.id }) }
  catch (e) { /* store handles */ }
}

// Assign
function openAssignModal(slot) {
  assignTarget.value = slot; selectedProduct.value = null; assignQuery.value = ''; productResults.value = []
  showAssignModal.value = true
}

async function searchProducts() {
  if (assignQuery.value.length < 2) { productResults.value = []; return }
  try {
    const res = await productAPI.search(assignQuery.value)
    productResults.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) { productResults.value = [] }
}

function selectProduct(p) { selectedProduct.value = p; assignQuery.value = p.name; productResults.value = [] }

async function assignProduct() {
  if (!selectedProduct.value || !assignTarget.value) return
  if (!selectedRack.value) return
  assigning.value = true
  try {
    await slotStore.assign(assignTarget.value.id, { product_id: selectedProduct.value.id })
    showAssignModal.value = false
    await slotStore.fetchList({ rack_id: selectedRack.value.id })
  } catch (e) { /* store handles */ } finally { assigning.value = false }
}

async function unassignSlot(slot) {
  if (!confirm(`Lepaskan produk dari slot "${slot.slot_code}"?`)) return
  if (!selectedRack.value) return
  try { await slotStore.unassign(slot.id); await slotStore.fetchList({ rack_id: selectedRack.value.id }) }
  catch (e) { /* store handles */ }
}

onMounted(async () => {
  await warehouseStore.fetchList({ per_page: 100 })
  warehouses.value = warehouseStore.warehouses || []
})
</script>
