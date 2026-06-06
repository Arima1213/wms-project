<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="router.push('/warehouses')" class="btn btn-sm btn-outline">
          <ArrowLeftIcon class="w-4 h-4" />
        </button>
        <div>
          <h1 class="text-xl font-semibold text-gray-800">{{ warehouse?.name }}</h1>
          <p class="text-sm text-gray-400 font-mono">{{ warehouse?.code }}</p>
        </div>
        <span :class="warehouse?.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
          class="px-2 py-1 rounded text-xs font-medium">
          {{ warehouse?.is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
      </div>
      <div class="flex gap-2">
        <button @click="openPlanogram" class="btn btn-outline">
          <MapIcon class="w-4 h-4" />
          Planogram
        </button>
        <button class="btn btn-primary">Edit Gudang</button>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">Total Zone</p>
        <p class="text-2xl font-bold text-gray-800">{{ zones.length }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">Total Rak</p>
        <p class="text-2xl font-bold text-gray-800">{{ racks.length }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">Total Slot</p>
        <p class="text-2xl font-bold text-gray-800">{{ slots.length }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">Utilisasi</p>
        <p class="text-2xl font-bold text-blue-600">{{ utilization }}%</p>
        <div class="mt-1 h-1.5 bg-gray-100 rounded-full">
          <div class="h-1.5 bg-blue-500 rounded-full" :style="{ width: utilization + '%' }"></div>
        </div>
      </div>
    </div>

    <!-- Zones & Racks -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Zones -->
      <div class="lg:col-span-2 card p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold">Zone</h3>
          <button class="btn btn-sm btn-outline">+ Tambah Zone</button>
        </div>
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-16 bg-gray-50 rounded animate-pulse"></div>
        </div>
        <div v-else-if="zones.length === 0" class="text-center py-8 text-gray-400 text-sm">
          Belum ada zone
        </div>
        <div v-else class="space-y-3">
          <div v-for="zone in zones" :key="zone.id" class="p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-gray-800">{{ zone.name }}</p>
                <p class="text-xs text-gray-400 font-mono">{{ zone.code }}</p>
              </div>
              <span :class="zone.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="px-2 py-1 rounded text-xs">
                {{ zone.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
            <!-- Racks in zone -->
            <div class="mt-3 grid grid-cols-4 gap-2">
              <div v-for="rack in (zone.racks || [])" :key="rack.id"
                class="p-2 bg-white rounded border border-gray-200 text-center">
                <p class="text-xs font-mono text-gray-600">{{ rack.code }}</p>
                <p class="text-xs text-gray-400">{{ (rack.levels || []).reduce((sum, l) => sum + (l.slots || []).length, 0) }} slot</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Warehouse Info -->
      <div class="card p-6">
        <h3 class="font-semibold mb-4">Info Gudang</h3>
        <div class="space-y-4">
          <div>
            <label class="text-xs text-gray-400">Tipe</label>
            <p class="text-sm font-medium text-gray-700 capitalize">{{ warehouse?.warehouse_type || '-' }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-400">Alamat</label>
            <p class="text-sm text-gray-700">{{ warehouse?.address || '-' }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-400">Kapasitas</label>
            <p class="text-sm text-gray-700">{{ warehouse?.capacity_m2 ? warehouse.capacity_m2 + ' m²' : '-' }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-400">Planogram</label>
            <p v-if="warehouse?.planogram" class="text-sm text-green-600">
              v{{ warehouse.planogram.version }} &middot;
              {{ formatDate(warehouse.planogram.updated_at) }}
            </p>
            <p v-else class="text-sm text-gray-400">Belum ada</p>
            <button v-if="warehouse?.planogram" @click="openPlanogram"
              class="text-xs text-blue-600 hover:underline mt-1">Buka Editor</button>
          </div>
          <div>
            <label class="text-xs text-gray-400">Dibuat</label>
            <p class="text-sm text-gray-700">{{ formatDate(warehouse?.created_at) }}</p>
          </div>
        </div>

        <!-- Mini planogram preview -->
        <div v-if="warehouse?.planogram?.canvas_data" class="mt-4 pt-4 border-t">
          <h4 class="text-xs font-medium text-gray-500 mb-2">Preview Planogram</h4>
          <div class="bg-gray-50 rounded overflow-hidden" style="height: 150px;">
            <canvas ref="previewCanvas" class="w-full h-full"></canvas>
          </div>
          <button @click="openPlanogram" class="btn btn-sm btn-outline w-full mt-2">
            <PencilIcon class="w-3 h-3" />
            Edit Planogram
          </button>
        </div>
        <div v-else class="mt-4 pt-4 border-t">
          <div class="text-center py-6">
            <MapIcon class="w-8 h-8 text-gray-200 mx-auto mb-2" />
            <p class="text-xs text-gray-400 mb-3">Belum ada planogram</p>
            <button @click="openPlanogram" class="btn btn-sm btn-primary w-full">
              Buat Planogram
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { warehouseAPI, planogramAPI } from '../services/api'
import {
  ArrowLeftIcon,
  MapIcon,
  PencilIcon
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const route = useRoute()
const router = useRouter()

const warehouse = ref(null)
const zones = ref([])
const racks = ref([])
const slots = ref([])
const loading = ref(true)
const previewCanvas = ref(null)

const utilization = computed(() => {
  if (!slots.value.length) return 0
  const filled = slots.value.filter(s => s.product_id).length
  return Math.round((filled / slots.value.length) * 100)
})

async function fetchData() {
  loading.value = true
  try {
    const whRes = await warehouseAPI.show(route.params.id)
    warehouse.value = whRes

    // Try to fetch planogram
    try {
      const pgRes = await planogramAPI.show(warehouse.value.id)
      if (pgRes.data) {
        warehouse.value.planogram = pgRes.data
      } else if (pgRes) {
        warehouse.value.planogram = pgRes
      }
    } catch {}

    // Load zones from API response (nested: zones -> racks -> levels -> slots)
    zones.value = warehouse.value.zones || []
    racks.value = []
    slots.value = []
    zones.value.forEach(zone => {
      ;(zone.racks || []).forEach(rack => {
        racks.value.push(rack)
        ;(rack.levels || []).forEach(level => {
          ;(level.slots || []).forEach(slot => {
            slots.value.push({ ...slot, rack_id: rack.id })
          })
        })
      })
    })
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function openPlanogram() {
  router.push(`/planograms/${warehouse.value.id}`)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    return format(new Date(dateStr), 'd MMM yyyy', { locale: id })
  } catch {
    return dateStr
  }
}

function drawPreview() {
  if (!previewCanvas.value || !warehouse.value?.planogram?.canvas_data) return
  const canvas = previewCanvas.value
  canvas.width = canvas.offsetWidth
  canvas.height = canvas.offsetHeight
  const ctx = canvas.getContext('2d')
  const data = warehouse.value.planogram.canvas_data
  ctx.fillStyle = '#f9fafb'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  if (data.zones) {
    data.zones.forEach(zone => {
      ctx.fillStyle = zone.color || '#e0e7ff'
      ctx.fillRect(zone.x || 0, zone.y || 0, (zone.width || 60) / 4, (zone.height || 40) / 4)
    })
  }
  if (data.items) {
    data.items.forEach(item => {
      ctx.fillStyle = item.color || '#6366f1'
      ctx.fillRect((item.x || 0) / 4, (item.y || 0) / 4, ((item.width || 20) - 2) / 4, ((item.height || 20) - 2) / 4)
    })
  }
}

onMounted(async () => {
  await fetchData()
  setTimeout(drawPreview, 100)
})
</script>