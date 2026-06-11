<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-white">Bin / Lokasi</h1>
        <p class="text-sm text-slate-400 mt-1">Kelola bin (sub-lokasi) di dalam rak</p>
      </div>
      <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        + Bin Baru
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-3 mb-4 flex-wrap">
      <input v-model="filters.search" @input="debouncedSearch" placeholder="Cari kode bin..."
        class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white w-48" />
      <select v-model="filters.bin_type" @change="applyFilters" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white">
        <option value="">Semua Tipe</option>
        <option value="storage">Storage</option>
        <option value="picking">Picking</option>
        <option value="receiving">Receiving</option>
        <option value="shipping">Shipping</option>
        <option value="overflow">Overflow</option>
        <option value="quarantine">Quarantine</option>
      </select>
      <select v-model="filters.warehouse_id" @change="applyFilters" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white">
        <option value="">Semua Gudang</option>
        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>
      <select v-model="filters.is_active" @change="applyFilters" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white">
        <option value="">Semua Status</option>
        <option value="true">Aktif</option>
        <option value="false">Nonaktif</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-slate-400">Memuat...</div>
      <table v-else-if="items.length" class="w-full">
        <thead class="bg-slate-700/50">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Kode</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Tipe</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Rak</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Level</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Gudang</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Status</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <tr v-for="b in items" :key="b.id" class="hover:bg-slate-700/30 transition-colors">
            <td class="px-4 py-3">
              <router-link :to="`/bins/${b.id}`" class="text-blue-400 hover:text-blue-300 font-medium">
                {{ b.code }}
              </router-link>
            </td>
            <td class="px-4 py-3"><span :class="typeClass(b.bin_type)" class="px-2 py-0.5 rounded text-xs">{{ binTypeLabel(b.bin_type) }}</span></td>
            <td class="px-4 py-3 text-sm text-slate-300">{{ b.rack?.code || '-' }}</td>
            <td class="px-4 py-3 text-sm text-slate-300">{{ b.level }}</td>
            <td class="px-4 py-3 text-sm text-slate-300">{{ b.rack?.zone?.warehouse?.name || '-' }}</td>
            <td class="px-4 py-3">
              <span :class="b.is_active ? 'bg-green-600 text-green-100' : 'bg-slate-600 text-slate-200'"
                class="px-2 py-0.5 rounded-full text-xs">{{ b.is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-1">
                <router-link :to="`/bins/${b.id}`" class="px-2 py-1 text-xs bg-slate-700 rounded hover:bg-slate-600 text-slate-300">Detail</router-link>
                <button @click="handleToggle(b)" class="px-2 py-1 text-xs bg-slate-700 rounded hover:bg-slate-600 text-slate-300">
                  {{ b.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="p-8 text-center text-slate-500">Belum ada data bin.</div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last > 1" class="flex justify-center gap-2 mt-4">
      <button @click="changePage(pagination.current - 1)" :disabled="pagination.current <= 1"
        class="px-3 py-1 bg-slate-700 rounded text-sm disabled:opacity-50">Prev</button>
      <span class="px-3 py-1 text-sm text-slate-400">{{ pagination.current }} / {{ pagination.last }}</span>
      <button @click="changePage(pagination.current + 1)" :disabled="pagination.current >= pagination.last"
        class="px-3 py-1 bg-slate-700 rounded text-sm disabled:opacity-50">Next</button>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showCreateModal = false">
      <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <h2 class="text-lg font-bold text-white mb-4">Buat Bin Baru</h2>
        <form @submit.prevent="handleCreate">
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="col-span-2">
              <label class="block text-sm text-slate-400 mb-1">Rak *</label>
              <select v-model="form.rack_id" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="">Pilih Rak</option>
                <option v-for="r in racks" :key="r.id" :value="r.id">{{ r.code }} ({{ r.zone?.name }})</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Kode</label>
              <input v-model="form.code" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" placeholder="Auto-generate jika kosong" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Tipe *</label>
              <select v-model="form.bin_type" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="storage">Storage</option>
                <option value="picking">Picking</option>
                <option value="receiving">Receiving</option>
                <option value="shipping">Shipping</option>
                <option value="overflow">Overflow</option>
                <option value="quarantine">Quarantine</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Level</label>
              <input v-model="form.level" type="number" min="1" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Posisi</label>
              <input v-model="form.position" type="number" min="0" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Max Berat (kg)</label>
              <input v-model="form.max_weight" type="number" step="0.01" min="0" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Max Volume (m³)</label>
              <input v-model="form.max_volume" type="number" step="0.01" min="0" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" />
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-700 rounded-lg text-sm text-slate-300 hover:bg-slate-600">Batal</button>
            <button type="submit" :disabled="loading" class="px-4 py-2 bg-blue-600 rounded-lg text-sm text-white hover:bg-blue-700 disabled:opacity-50">
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useBinStore } from '../stores/bin'
import { useWarehouseStore } from '../stores/warehouse'

const binStore = useBinStore()
const warehouseStore = useWarehouseStore()

const items = computed(() => binStore.items)
const pagination = computed(() => binStore.pagination)
const loading = computed(() => binStore.loading)
const warehouses = computed(() => warehouseStore.items)
const racks = computed(() => warehouseStore.racks || [])

const filters = reactive({ rack_id: '', warehouse_id: '', bin_type: '', is_active: '', search: '' })
const showCreateModal = ref(false)
let debounceTimer = null

const form = reactive({ rack_id: '', code: '', bin_type: 'storage', level: 1, position: 0, max_weight: null, max_volume: null, is_active: true })

function binTypeLabel(t) {
  const m = { storage: 'Storage', picking: 'Picking', receiving: 'Receiving', shipping: 'Shipping', overflow: 'Overflow', quarantine: 'Quarantine' }
  return m[t] || t
}
function typeClass(t) {
  const m = { storage: 'bg-blue-600/20 text-blue-300', picking: 'bg-green-600/20 text-green-300', receiving: 'bg-yellow-600/20 text-yellow-300', shipping: 'bg-purple-600/20 text-purple-300', overflow: 'bg-orange-600/20 text-orange-300', quarantine: 'bg-red-600/20 text-red-300' }
  return m[t] || 'bg-slate-600/20'
}

function debouncedSearch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(applyFilters, 300) }

async function applyFilters() {
  binStore.filters = { ...filters }
  await binStore.fetchBins()
}

async function changePage(page) { await binStore.fetchBins({ page }) }

async function handleCreate() {
  try {
    await binStore.createBin({ ...form })
    showCreateModal.value = false
    form.level = 1; form.position = 0; form.max_weight = null; form.max_volume = null; form.code = ''
    await binStore.fetchBins()
  } catch (e) { alert('Gagal menyimpan bin') }
}

async function handleToggle(b) {
  await binStore.toggleActive(b.id)
  await binStore.fetchBins()
}

onMounted(async () => {
  await Promise.all([
    binStore.fetchBins(),
    warehouseStore.fetchWarehouses(),
  ])
})
</script>
