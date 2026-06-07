<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-gray-800">Planogram</h1>
        <p class="text-sm text-gray-500 mt-1">Visualisasi tata letak rak gudang</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="showCreateModal = true" class="btn btn-primary">
          <PlusIcon class="w-4 h-4" />
          Buat Planogram
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="card p-4">
      <div class="flex items-center gap-4">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari gudang..."
            class="input w-full"
          />
        </div>
        <select v-model="filterStatus" class="input w-48">
          <option value="">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="draft">Draft</option>
        </select>
      </div>
    </div>

    <!-- Warehouse Planogram Cards -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="card p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
        <div class="h-3 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-1/4"></div>
      </div>
    </div>

    <div v-else-if="error" class="card p-8 text-center">
      <ExclamationTriangleIcon class="w-12 h-12 text-red-400 mx-auto mb-3" />
      <p class="text-red-600 font-medium">Gagal memuat planogram</p>
      <p class="text-gray-500 text-sm mt-1">{{ error }}</p>
      <button @click="fetchWarehouses" class="btn btn-primary mt-4">Coba Lagi</button>
    </div>

    <div v-else-if="filteredWarehouses.length === 0" class="card p-8 text-center">
      <MapIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500">Tidak ada gudang yang cocok</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="wh in filteredWarehouses"
        :key="wh.id"
        class="card p-6 hover:shadow-md transition-shadow cursor-pointer"
        @click="openPlanogram(wh)"
      >
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="font-semibold text-gray-800">{{ wh.name }}</h3>
            <p class="text-xs text-gray-400 font-mono">{{ wh.code }}</p>
          </div>
          <span :class="wh.planogram ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
            class="px-2 py-1 rounded text-xs font-medium">
            {{ wh.planogram ? 'v' + wh.planogram.version : 'Belum ada' }}
          </span>
        </div>

        <!-- Mini preview canvas -->
        <div class="bg-gray-50 rounded-lg mb-4 overflow-hidden" style="height: 120px;">
          <div v-if="wh.planogram?.canvas_data" class="w-full h-full relative">
            <canvas ref="miniCanvasRefs" :data-warehouse-id="wh.id" class="w-full h-full"></canvas>
          </div>
          <div v-else class="w-full h-full flex items-center justify-center">
            <MapIcon class="w-8 h-8 text-gray-300" />
          </div>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-400">
          <span v-if="wh.planogram?.updated_at">
            Diubah {{ formatDate(wh.planogram.updated_at) }}
          </span>
          <span v-else>Belum pernah diedit</span>
          <span v-if="wh.planogram?.createdBy?.name">{{ wh.planogram.createdBy.name }}</span>
        </div>

        <!-- Quick actions -->
        <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">
          <button
            v-if="wh.planogram"
            @click.stop="openPlanogram(wh)"
            class="flex-1 btn btn-sm btn-outline"
          >
            <PencilIcon class="w-3 h-3" />
            Edit
          </button>
          <button
            v-if="wh.planogram"
            @click.stop="showHistory(wh)"
            class="flex-1 btn btn-sm btn-outline"
          >
            <ClockIcon class="w-3 h-3" />
            Riwayat
          </button>
          <button
            v-else
            @click.stop="openPlanogram(wh)"
            class="flex-1 btn btn-sm btn-primary"
          >
            <PlusIcon class="w-3 h-3" />
            Buat
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Planogram Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-semibold text-lg mb-4">Buat Planogram Baru</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Gudang</label>
            <select v-model="createForm.warehouse_id" class="input w-full">
              <option value="">Pilih Gudang</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                {{ wh.code }} - {{ wh.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
            <input v-model="createForm.change_summary" type="text" class="input w-full"
              placeholder="Deskripsi perubahan..." />
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="showCreateModal = false" class="btn btn-outline">Batal</button>
          <button @click="createPlanogram" class="btn btn-primary" :disabled="!createForm.warehouse_id">
            Buat & Edit
          </button>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-lg">Riwayat Planogram</h3>
            <p class="text-sm text-gray-500">{{ historyWarehouse?.name }}</p>
          </div>
          <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <div class="flex-1 overflow-auto p-6">
          <div v-if="historyLoading" class="space-y-3">
            <div v-for="i in 5" :key="i" class="h-16 bg-gray-50 rounded animate-pulse"></div>
          </div>
          <div v-else-if="history.length === 0" class="text-center py-8 text-gray-400">
            Belum ada riwayat
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="snap in history"
              :key="snap.id"
              class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-sm text-gray-600">{{ snap.version }}</span>
                  <span v-if="snap.change_summary" class="text-sm text-gray-500">{{ snap.change_summary }}</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                  {{ snap.createdBy?.name }} &middot; {{ formatDate(snap.created_at) }}
                </p>
              </div>
              <button
                @click="restoreSnapshot(snap)"
                class="btn btn-sm btn-outline"
                :disabled="restoringId === snap.id"
              >
                <ArrowPathIcon class="w-3 h-3" />
                {{ restoringId === snap.id ? 'Memuat...' : 'Pulihkan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useWarehouseStore } from '../stores/warehouse'
import { planogramAPI } from '../services/api'
import { useNotificationStore } from '../stores/notification'
import {
  PlusIcon,
  MapIcon,
  PencilIcon,
  ClockIcon,
  XMarkIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const router = useRouter()
const warehouseStore = useWarehouseStore()
const notify = useNotificationStore()

const warehouses = ref([])
const loading = ref(true)
const error = ref(null)
const searchQuery = ref('')
const filterStatus = ref('')
const showCreateModal = ref(false)
const showHistoryModal = ref(false)
const historyWarehouse = ref(null)
const history = ref([])
const historyLoading = ref(false)
const restoringId = ref(null)
const miniCanvasRefs = ref([])

const createForm = ref({
  warehouse_id: '',
  change_summary: ''
})

const filteredWarehouses = computed(() => {
  return warehouses.value.filter(wh => {
    const matchesSearch = !searchQuery.value ||
      wh.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      wh.code.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesSearch
  })
})

async function fetchWarehouses() {
  loading.value = true
  error.value = null
  try {
    await warehouseStore.fetchList({ per_page: 100 })
    const list = warehouseStore.warehouses
    
    // Fetch planogram for each warehouse
    const withPlanograms = await Promise.all(
      list.map(async (wh) => {
        try {
          const pgRes = await planogramAPI.show(wh.id)
          return { ...wh, planogram: pgRes.data || pgRes || null }
        } catch {
          return { ...wh, planogram: null }
        }
      })
    )
    warehouses.value = withPlanograms
  } catch (e) {
    error.value = e.message || 'Terjadi kesalahan saat memuat data'
    notify.error(error.value)
  } finally {
    loading.value = false
  }
}

function openPlanogram(wh) {
  router.push({ name: 'PlanogramEditor', params: { warehouseId: wh.id } })
}

async function showHistory(wh) {
  historyWarehouse.value = wh
  showHistoryModal.value = true
  historyLoading.value = true
  try {
    const res = await planogramAPI.history(wh.id)
    history.value = res.data || []
  } catch (e) {
    history.value = []
    notify.error('Gagal memuat riwayat planogram')
  } finally {
    historyLoading.value = false
  }
}

async function restoreSnapshot(snap) {
  if (!historyWarehouse.value) return
  restoringId.value = snap.id
  try {
    await planogramAPI.update(historyWarehouse.value.id, {
      canvas_data: snap.canvas_data,
      change_summary: `Dipulihkan dari versi ${snap.version}`
    })
    await fetchWarehouses()
    showHistoryModal.value = false
  } catch (e) {
    alert('Gagal memulihkan: ' + e.message)
  } finally {
    restoringId.value = null
  }
}

async function createPlanogram() {
  if (!createForm.value.warehouse_id) return
  const whId = createForm.value.warehouse_id
  try {
    // Create empty planogram
    await planogramAPI.update(whId, {
      canvas_data: { items: [], zones: [] },
      change_summary: createForm.value.change_summary || 'Planogram dibuat'
    })
    showCreateModal.value = false
    createForm.value = { warehouse_id: '', change_summary: '' }
    await fetchWarehouses()
    // Open editor
    const wh = warehouses.value.find(w => w.id === whId)
    if (wh) openPlanogram(wh)
  } catch (e) {
    alert('Gagal membuat planogram: ' + e.message)
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    return format(new Date(dateStr), 'd MMM yyyy, HH:mm', { locale: id })
  } catch {
    return dateStr
  }
}

function drawMiniPlanogram(wh) {
  if (!wh.planogram?.canvas_data) return
  const canvas = document.querySelector(`canvas[data-warehouse-id="${wh.id}"]`)
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const data = wh.planogram.canvas_data
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  ctx.fillStyle = '#f9fafb'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  if (data.zones) {
    data.zones.forEach(zone => {
      ctx.fillStyle = zone.color || '#e0e7ff'
      ctx.fillRect(zone.x || 0, zone.y || 0, zone.width || 60, zone.height || 40)
      ctx.strokeStyle = '#c7d2fe'
      ctx.strokeRect(zone.x || 0, zone.y || 0, zone.width || 60, zone.height || 40)
    })
  }
  if (data.items) {
    data.items.forEach(item => {
      ctx.fillStyle = '#6366f1'
      ctx.fillRect(item.x || 0, item.y || 0, (item.width || 20) - 2, (item.height || 20) - 2)
    })
  }
}

onMounted(async () => {
  await fetchWarehouses()
  await nextTick()
  warehouses.value.forEach(wh => {
    if (wh.planogram) {
      // Set canvas size
      const canvas = document.querySelector(`canvas[data-warehouse-id="${wh.id}"]`)
      if (canvas) {
        canvas.width = canvas.offsetWidth
        canvas.height = canvas.offsetHeight
        drawMiniPlanogram(wh)
      }
    }
  })
})
</script>