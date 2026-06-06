<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button @click="router.push('/warehouses')" class="btn btn-sm btn-ghost p-2 -ml-2">
          <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ warehouse?.name }}</h1>
            <StatusBadge :status="warehouse?.is_active ? 'active' : 'inactive'" :label="warehouse?.is_active ? 'Aktif' : 'Nonaktif'" />
          </div>
          <p class="text-sm text-gray-500 font-mono mt-0.5">{{ warehouse?.code }}</p>
        </div>
      </div>
      <div class="flex gap-3">
        <button @click="openPlanogram" class="btn btn-outline shadow-sm">
          <MapIcon class="w-4 h-4 text-indigo-500" />
          Planogram
        </button>
        <button class="btn btn-primary shadow-sm" @click="showEditModal = true">
          <PencilIcon class="w-4 h-4" />
          Edit Gudang
        </button>
      </div>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-4 gap-6 animate-pulse">
      <div class="card h-28 bg-white" v-for="i in 4" :key="i"></div>
    </div>

    <!-- Stats Row -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card p-5 border-l-4 border-l-blue-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Zone</p>
        <p class="text-3xl font-bold text-gray-800">{{ zones.length }}</p>
      </div>
      <div class="card p-5 border-l-4 border-l-indigo-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Rak</p>
        <p class="text-3xl font-bold text-gray-800">{{ racks.length }}</p>
      </div>
      <div class="card p-5 border-l-4 border-l-purple-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Slot</p>
        <p class="text-3xl font-bold text-gray-800">{{ slots.length }}</p>
      </div>
      <div class="card p-5 border-l-4 border-l-emerald-500">
        <div class="flex justify-between items-end mb-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilisasi</p>
          <p class="text-3xl font-bold text-emerald-600">{{ utilization }}%</p>
        </div>
        <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" :style="{ width: utilization + '%' }"></div>
        </div>
      </div>
    </div>

    <!-- Zones & Racks -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Zones -->
      <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between bg-white px-5 py-4 rounded-xl border border-gray-100 shadow-sm">
          <h3 class="font-bold text-gray-800">Daftar Zone</h3>
          <button @click="openCreateZone" class="btn btn-sm btn-primary">
            + Tambah Zone
          </button>
        </div>

        <div v-if="loading" class="space-y-4">
          <div v-for="i in 3" :key="i" class="h-24 bg-white border border-gray-100 rounded-xl animate-pulse"></div>
        </div>
        <div v-else-if="zones.length === 0" class="card py-12 text-center text-gray-400">
          Belum ada zone yang didaftarkan.
        </div>
        <div v-else class="space-y-4">
          <div v-for="zone in zones" :key="zone.id" class="card overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-5 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: zone.color || '#3b82f6' }"></div>
                <div>
                  <p class="font-semibold text-gray-800">{{ zone.name }}</p>
                  <p class="text-xs text-gray-500 font-mono">{{ zone.code }} &middot; {{ zone.zone_type?.replace('_', ' ') || 'fast moving' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <StatusBadge :status="zone.is_active ? 'active' : 'inactive'" />
                <button @click="openEditZone(zone)" class="p-1 text-gray-400 hover:text-blue-600 transition-colors">
                  <PencilIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <div class="p-5">
              <div v-if="!(zone.racks && zone.racks.length)" class="text-sm text-gray-400 italic">
                Tidak ada rak di zone ini.
              </div>
              <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                <div v-for="rack in zone.racks" :key="rack.id"
                  class="p-3 bg-white rounded-lg border border-gray-200 text-center hover:border-blue-400 hover:shadow-sm transition-all cursor-pointer group">
                  <p class="text-sm font-mono font-bold text-gray-700 group-hover:text-blue-600">{{ rack.code }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ (rack.levels || []).reduce((sum, l) => sum + (l.slots || []).length, 0) }} slot</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Warehouse Info -->
      <div class="space-y-6">
        <div class="card p-6">
          <h3 class="font-bold text-gray-800 mb-5 pb-4 border-b border-gray-100">Informasi Gudang</h3>
          <div class="space-y-5">
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tipe</label>
              <p class="text-sm font-medium text-gray-800 capitalize mt-1">{{ warehouse?.warehouse_type?.replace('_', ' ') || '-' }}</p>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Alamat</label>
              <p class="text-sm text-gray-700 mt-1">{{ warehouse?.address || '-' }}</p>
              <p class="text-sm text-gray-500">{{ warehouse?.city }} {{ warehouse?.province }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kapasitas</label>
                <p class="text-sm text-gray-800 mt-1">{{ warehouse?.capacity_m2 ? warehouse.capacity_m2 + ' m²' : '-' }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Dibuat</label>
                <p class="text-sm text-gray-800 mt-1">{{ formatDate(warehouse?.created_at) }}</p>
              </div>
            </div>
            
            <div class="pt-4 border-t border-gray-100">
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Planogram</label>
              <div v-if="warehouse?.planogram" class="mt-2 flex items-center justify-between">
                <div>
                  <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-medium border border-green-200">
                    v{{ warehouse.planogram.version }}
                  </span>
                  <p class="text-xs text-gray-500 mt-1">Diperbarui: {{ formatDate(warehouse.planogram.updated_at) }}</p>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500 mt-1">Belum dikonfigurasi</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Zone Modal -->
    <ZoneModal 
      v-model="showZoneModal" 
      :warehouse-id="warehouse?.id" 
      :editing-zone="selectedZone"
      @saved="fetchData" 
    />

    <!-- Edit Modal placeholder -->
    <Modal v-model="showEditModal" title="Edit Gudang">
      <div class="text-center py-4 text-gray-500">
        Fitur edit menggunakan modal utama di Warehouses.vue, ini placeholder untuk detail view.
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWarehouseStore } from '../stores/warehouse'
import StatusBadge from '../components/common/StatusBadge.vue'
import Modal from '../components/common/Modal.vue'
import ZoneModal from '../components/warehouse/ZoneModal.vue'
import {
  ArrowLeftIcon,
  MapIcon,
  PencilIcon
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const route = useRoute()
const router = useRouter()
const store = useWarehouseStore()

const warehouse = computed(() => store.selected)
const loading = computed(() => store.loading)
const showEditModal = ref(false)

const showZoneModal = ref(false)
const selectedZone = ref(null)

const zones = computed(() => warehouse.value?.zones || [])
const racks = computed(() => {
  const r = []
  zones.value.forEach(z => {
    if (z.racks) r.push(...z.racks)
  })
  return r
})

const slots = computed(() => {
  const s = []
  racks.value.forEach(r => {
    if (r.levels) {
      r.levels.forEach(l => {
        if (l.slots) s.push(...l.slots)
      })
    }
  })
  return s
})

const utilization = computed(() => {
  if (!slots.value.length) return 0
  const filled = slots.value.filter(s => s.fixed_product_id || (s.stocks && s.stocks.length)).length
  return Math.round((filled / slots.value.length) * 100)
})

async function fetchData() {
  await store.fetchOne(route.params.id)
}

function openPlanogram() {
  if (warehouse.value?.id) {
    router.push(`/planograms/${warehouse.value.id}`)
  }
}

function openCreateZone() {
  selectedZone.value = null
  showZoneModal.value = true
}

function openEditZone(zone) {
  selectedZone.value = zone
  showZoneModal.value = true
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    return format(new Date(dateStr), 'd MMM yyyy', { locale: id })
  } catch {
    return dateStr
  }
}

onMounted(() => {
  fetchData()
})
</script>