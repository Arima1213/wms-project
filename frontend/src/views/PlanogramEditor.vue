<template>
  <div class="flex flex-col h-full">
    <!-- Toolbar -->
    <div class="bg-white border-b px-4 py-3 flex items-center gap-3 flex-wrap">
      <button @click="router.push('/planograms')" class="btn btn-sm btn-outline">
        <ArrowLeftIcon class="w-4 h-4" />
        Kembali
      </button>
      <div class="h-5 w-px bg-gray-200"></div>
      <span class="text-sm font-medium text-gray-700">{{ warehouse?.name }} ({{ warehouse?.code }})</span>
      <span v-if="currentPlanogram" class="text-xs text-gray-400">v{{ currentPlanogram.version }}</span>
      <div class="h-5 w-px bg-gray-200"></div>

      <!-- Tool mode -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
        <button
          v-for="tool in tools"
          :key="tool.id"
          @click="activeTool = tool.id"
          :class="activeTool === tool.id ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'"
          class="px-3 py-1.5 rounded-md text-sm font-medium transition-all flex items-center gap-1.5"
        >
          <component :is="tool.icon" class="w-4 h-4" />
          {{ tool.label }}
        </button>
      </div>

      <div class="h-5 w-px bg-gray-200"></div>

      <!-- Zoom controls -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
        <button @click="zoomOut" class="px-2 py-1 rounded hover:bg-white text-gray-600 hover:text-gray-800 text-sm" title="Perkecil">
          <MinusIcon class="w-4 h-4" />
        </button>
        <span class="px-2 text-xs font-mono text-gray-500 min-w-[48px] text-center">{{ Math.round(zoomLevel * 100) }}%</span>
        <button @click="zoomIn" class="px-2 py-1 rounded hover:bg-white text-gray-600 hover:text-gray-800 text-sm" title="Perbesar">
          <PlusIcon class="w-4 h-4" />
        </button>
        <button @click="zoomFit" class="px-2 py-1 rounded hover:bg-white text-gray-600 hover:text-gray-800 text-sm" title="Sesuaikan">
          <ArrowPathIcon class="w-4 h-4" />
        </button>
      </div>

      <div class="h-5 w-px bg-gray-200"></div>

      <!-- Grid snap -->
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="gridSnap" class="rounded" />
        Grid Snap ({{ gridSize }}px)
      </label>

      <div class="flex-1"></div>

      <!-- Save status -->
      <span v-if="saving" class="text-xs text-gray-400 flex items-center gap-1">
        <ArrowPathIcon class="w-3 h-3 animate-spin" />
        Menyimpan...
      </span>
      <span v-else-if="lastSaved" class="text-xs text-green-600">
        Tersimpan {{ formatTime(lastSaved) }}
      </span>

      <button @click="savePlanogram" class="btn btn-sm btn-outline" :disabled="saving">
        <BookmarkIcon class="w-4 h-4" />
        Simpan
      </button>
      <button @click="takeSnapshot" class="btn btn-sm btn-outline" :disabled="saving">
        <CameraIcon class="w-4 h-4" />
        Snapshot
      </button>
    </div>

    <div class="flex flex-1 overflow-hidden">
      <!-- Left Sidebar: Product Search -->
      <aside class="w-72 bg-white border-r flex flex-col overflow-hidden">
        <div class="p-4 border-b">
          <h3 class="font-semibold text-sm text-gray-700 mb-3">Produk</h3>
          <div class="relative">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              v-model="productSearch"
              @input="searchProducts"
              type="text"
              placeholder="Cari nama/SKU/barcode..."
              class="input pl-9 w-full text-sm"
            />
          </div>
        </div>

        <div class="flex-1 overflow-auto p-3 space-y-2">
          <div v-if="productSearchLoading" class="space-y-2">
            <div v-for="i in 4" :key="i" class="h-14 bg-gray-50 rounded animate-pulse"></div>
          </div>
          <div v-else-if="searchResults.length === 0 && productSearch.length > 0" class="text-center py-6 text-gray-400 text-sm">
            Produk tidak ditemukan
          </div>
          <div v-else-if="searchResults.length > 0" class="space-y-2">
            <div
              v-for="product in searchResults"
              :key="product.id"
              draggable="true"
              @dragstart="onProductDragStart($event, product)"
              class="p-3 bg-gray-50 hover:bg-blue-50 rounded-lg cursor-grab active:cursor-grabbing border border-gray-100 hover:border-blue-200 transition-colors"
            >
              <p class="text-sm font-medium text-gray-800 truncate">{{ product.name }}</p>
              <div class="flex items-center gap-2 mt-1">
                <span class="font-mono text-xs text-gray-400">{{ product.sku }}</span>
                <span v-if="product.barcode" class="font-mono text-xs text-gray-300">{{ product.barcode }}</span>
              </div>
              <p v-if="product.category" class="text-xs text-gray-400 mt-1">{{ product.category.name }}</p>
            </div>
          </div>
          <div v-else class="text-center py-6 text-gray-400 text-sm">
            <MagnifyingGlassIcon class="w-8 h-8 mx-auto mb-2 text-gray-200" />
            Ketik untuk mencari produk
          </div>
        </div>

        <!-- Planogram Items List -->
        <div class="border-t p-4">
          <h3 class="font-semibold text-sm text-gray-700 mb-2">Item di Planogram ({{ planogramItems.length }})</h3>
          <div class="space-y-1 max-h-48 overflow-auto">
            <div
              v-for="(item, idx) in planogramItems"
              :key="item.id || idx"
              class="flex items-center gap-2 p-2 bg-gray-50 rounded text-xs"
            >
              <div class="w-3 h-3 rounded" :style="{ backgroundColor: item.color || '#6366f1' }"></div>
              <span class="flex-1 truncate text-gray-700">{{ item.product_name || 'Item ' + (idx + 1) }}</span>
              <button @click="removeItem(idx)" class="text-red-400 hover:text-red-600">
                <TrashIcon class="w-3 h-3" />
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- Canvas Area -->
      <div class="flex-1 overflow-auto bg-gray-100 p-4" ref="canvasContainer"
        @dragover.prevent
        @drop.prevent="onCanvasDrop"
      >
        <div class="relative bg-white shadow-lg rounded-lg overflow-hidden" :style="canvasContainerStyle">
          <!-- Grid overlay -->
          <div v-if="gridSnap" class="absolute inset-0 pointer-events-none" :style="gridOverlayStyle"></div>

          <!-- Konva Stage -->
          <v-stage
            ref="stageRef"
            :config="stageConfig"
            @click="onStageClick"
            @mousedown="onStageMouseDown"
            @mousemove="onStageMouseMove"
            @mouseup="onStageMouseUp"
            @dragmove="onItemDragMove"
          >
            <!-- Background -->
            <v-layer>
              <v-rect :config="bgConfig" />
            </v-layer>

            <!-- Zones (rack areas) -->
            <v-layer ref="zonesLayer">
              <v-rect
                v-for="(zone, idx) in zones"
                :key="'zone-' + idx"
                :config="zone.config"
                @click="selectZone(idx)"
                @dragend="onZoneDragEnd(idx, $event)"
              />
              <v-text
                v-for="(zone, idx) in zones"
                :key="'zone-label-' + idx"
                :config="{
                  x: (zone.config.x || 0) + 4,
                  y: (zone.config.y || 0) + 4,
                  text: zone.label || 'Zone ' + (idx + 1),
                  fontSize: 11,
                  fill: '#6b7280',
                  fontFamily: 'Plus Jakarta Sans, sans-serif',
                }"
              />
            </v-layer>

            <!-- Items (products on racks) -->
            <v-layer ref="itemsLayer">
              <v-group
                v-for="(item, idx) in planogramItems"
                :key="'item-' + idx"
                :config="{
                  x: item.x,
                  y: item.y,
                  draggable: activeTool === 'select',
                }"
                @click="selectItem(idx)"
                @dragend="onItemDragEnd(idx, $event)"
              >
                <v-rect :config="{
                  width: item.width || 40,
                  height: item.height || 40,
                  fill: item.color || '#6366f1',
                  cornerRadius: 4,
                  shadowColor: 'black',
                  shadowBlur: selectedItemIdx === idx ? 8 : 2,
                  shadowOpacity: selectedItemIdx === idx ? 0.3 : 0.1,
                }" />
                <v-text :config="{
                  text: (item.product_name || '?').substring(0, 6),
                  fontSize: 9,
                  fill: 'white',
                  width: item.width || 40,
                  align: 'center',
                  fontFamily: 'Plus Jakarta Sans, sans-serif',
                }" />
              </v-group>
            </v-layer>

            <!-- Drawing preview -->
            <v-layer v-if="isDrawing">
              <v-rect :config="drawingPreview" />
            </v-layer>
          </v-stage>
        </div>
      </div>

      <!-- Right Sidebar: Properties -->
      <aside class="w-64 bg-white border-l flex flex-col overflow-hidden">
        <div class="p-4 border-b">
          <h3 class="font-semibold text-sm text-gray-700">Properti</h3>
        </div>
        <div class="flex-1 overflow-auto p-4 space-y-4">
          <!-- Selected Item Properties -->
          <div v-if="selectedItemIdx !== null && planogramItems[selectedItemIdx]">
            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-3">Item Terpilih</h4>
            <div class="space-y-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Nama Produk</label>
                <input
                  :value="planogramItems[selectedItemIdx].product_name"
                  @input="updateItemProp('product_name', $event.target.value)"
                  type="text"
                  class="input input-sm w-full"
                />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">X</label>
                  <input
                    :value="Math.round(planogramItems[selectedItemIdx].x)"
                    @input="updateItemProp('x', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Y</label>
                  <input
                    :value="Math.round(planogramItems[selectedItemIdx].y)"
                    @input="updateItemProp('y', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Lebar</label>
                  <input
                    :value="planogramItems[selectedItemIdx].width || 40"
                    @input="updateItemProp('width', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Tinggi</label>
                  <input
                    :value="planogramItems[selectedItemIdx].height || 40"
                    @input="updateItemProp('height', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Warna</label>
                <input
                  :value="planogramItems[selectedItemIdx].color || '#6366f1'"
                  @input="updateItemProp('color', $event.target.value)"
                  type="color"
                  class="w-full h-8 rounded cursor-pointer"
                />
              </div>
              <button @click="removeItem(selectedItemIdx)" class="btn btn-sm btn-danger w-full">
                <TrashIcon class="w-3 h-3" />
                Hapus Item
              </button>
            </div>
          </div>

          <!-- Selected Zone Properties -->
          <div v-else-if="selectedZoneIdx !== null && zones[selectedZoneIdx]">
            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-3">Zone Terpilih</h4>
            <div class="space-y-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Nama Zone</label>
                <input
                  :value="zones[selectedZoneIdx].label"
                  @input="updateZoneProp('label', $event.target.value)"
                  type="text"
                  class="input input-sm w-full"
                />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">X</label>
                  <input
                    :value="Math.round(zones[selectedZoneIdx].config.x)"
                    @input="updateZoneProp('config.x', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Y</label>
                  <input
                    :value="Math.round(zones[selectedZoneIdx].config.y)"
                    @input="updateZoneProp('config.y', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Lebar</label>
                  <input
                    :value="Math.round(zones[selectedZoneIdx].config.width)"
                    @input="updateZoneProp('config.width', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Tinggi</label>
                  <input
                    :value="Math.round(zones[selectedZoneIdx].config.height)"
                    @input="updateZoneProp('config.height', Number($event.target.value))"
                    type="number"
                    class="input input-sm w-full"
                  />
                </div>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Warna</label>
                <input
                  :value="zones[selectedZoneIdx].config.fill || '#e0e7ff'"
                  @input="updateZoneProp('config.fill', $event.target.value)"
                  type="color"
                  class="w-full h-8 rounded cursor-pointer"
                />
              </div>
              <button @click="deleteZone(selectedZoneIdx)" class="btn btn-sm btn-danger w-full">
                <TrashIcon class="w-3 h-3" />
                Hapus Zone
              </button>
            </div>
          </div>

          <!-- Canvas Settings -->
          <div>
            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-3">Canvas</h4>
            <div class="space-y-3">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Lebar</label>
                  <input v-model.number="canvasWidth" type="number" class="input input-sm w-full" @change="onCanvasResize" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Tinggi</label>
                  <input v-model.number="canvasHeight" type="number" class="input input-sm w-full" @change="onCanvasResize" />
                </div>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Grid Size</label>
                <input v-model.number="gridSize" type="number" class="input input-sm w-full" min="10" />
              </div>
            </div>
          </div>

          <!-- Keyboard shortcuts -->
          <div class="text-xs text-gray-400 space-y-1">
            <p class="font-medium text-gray-500">Shortcut:</p>
            <p><kbd class="bg-gray-100 px-1 rounded">Del</kbd> Hapus terpilih</p>
            <p><kbd class="bg-gray-100 px-1 rounded">Esc</kbd> Batal pilih</p>
            <p><kbd class="bg-gray-100 px-1 rounded">Ctrl+S</kbd> Simpan</p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { warehouseAPI, planogramAPI } from '../services/api'
import { useNotificationStore } from '../stores/notification'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BookmarkIcon,
  CameraIcon,
  MagnifyingGlassIcon,
  TrashIcon,
  CursorArrowRaysIcon,
  RectangleStackIcon,
  ViewfinderCircleIcon,
  MinusIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const route = useRoute()
const router = useRouter()
const notify = useNotificationStore()

const warehouseId = computed(() => route.params.warehouseId)
const warehouse = ref(null)
const currentPlanogram = ref(null)
const saving = ref(false)
const lastSaved = ref(null)

// Canvas
const canvasWidth = ref(800)
const canvasHeight = ref(600)
const gridSize = ref(20)
const gridSnap = ref(true)
const zoomLevel = ref(1)
const stageRef = ref(null)
const canvasContainer = ref(null)

// Tools
const tools = [
  { id: 'select', label: 'Select', icon: CursorArrowRaysIcon },
  { id: 'zone', label: 'Zone', icon: RectangleStackIcon },
  { id: 'item', label: 'Item', icon: ViewfinderCircleIcon },
]
const activeTool = ref('select')

// Data
const zones = ref([])
const planogramItems = ref([])
const selectedZoneIdx = ref(null)
const selectedItemIdx = ref(null)

// Drawing
const isDrawing = ref(false)
const drawStart = ref({ x: 0, y: 0 })
const drawingPreview = ref({})

// Product search
const productSearch = ref('')
const productSearchLoading = ref(false)
const searchResults = ref([])
let searchTimeout = null

// Auto-save
let autoSaveTimer = null
const hasChanges = ref(false)

const stageConfig = computed(() => ({
  width: canvasWidth.value,
  height: canvasHeight.value,
}))

const bgConfig = computed(() => ({
  x: 0, y: 0,
  width: canvasWidth.value,
  height: canvasHeight.value,
  fill: '#ffffff',
}))

const canvasContainerStyle = computed(() => ({
  width: canvasWidth.value + 'px',
  height: canvasHeight.value + 'px',
  transform: `scale(${zoomLevel.value})`,
  transformOrigin: '0 0',
}))

function zoomIn() {
  zoomLevel.value = Math.min(zoomLevel.value + 0.25, 3)
}

function zoomOut() {
  zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25)
}

function zoomFit() {
  const container = canvasContainer.value?.parentElement
  if (!container) return
  const pad = 40
  const sx = (container.clientWidth - pad) / canvasWidth.value
  const sy = (container.clientHeight - pad) / canvasHeight.value
  zoomLevel.value = Math.min(sx, sy, 1)
}

const gridOverlayStyle = computed(() => {
  const size = gridSize.value
  const cols = Math.ceil(canvasWidth.value / size)
  const rows = Math.ceil(canvasHeight.value / size)
  const bg = []
  for (let r = 0; r <= rows; r++) {
    bg.push(`hsl(220, 10%, 93%) ${r * size}px`)
  }
  for (let c = 0; c <= cols; c++) {
    bg.push(`hsl(220, 10%, 93%) ${c * size}px`)
  }
  return {
    backgroundImage: `
      linear-gradient(to right, hsl(220, 10%, 93%) 1px, transparent 1px),
      linear-gradient(to bottom, hsl(220, 10%, 93%) 1px, transparent 1px)
    `,
    backgroundSize: `${size}px ${size}px`,
    width: canvasWidth.value + 'px',
    height: canvasHeight.value + 'px',
  }
})

const ZONE_COLORS = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#e0e7ff', '#fee2e2']
let zoneColorIdx = 0

function snapToGrid(val) {
  return gridSnap.value ? Math.round(val / gridSize.value) * gridSize.value : val
}

async function loadPlanogram() {
  try {
    const res = await planogramAPI.show(warehouseId.value)
    const pg = res.data
    currentPlanogram.value = pg
    if (pg?.canvas_data) {
      const data = pg.canvas_data
      zones.value = (data.zones || []).map((z, i) => ({
        label: z.label || `Zone ${i + 1}`,
        config: {
          x: z.x || 0, y: z.y || 0,
          width: z.width || 100, height: z.height || 80,
          fill: z.color || ZONE_COLORS[i % ZONE_COLORS.length],
          stroke: '#6366f1',
          strokeWidth: 1,
          draggable: true,
          cornerRadius: 6,
        }
      }))
      planogramItems.value = (data.items || []).map(item => ({
        ...item,
        color: item.color || '#6366f1',
      }))
 }
    if (pg?.canvas_settings) {
      const settings = pg.canvas_settings
      if (settings.canvas_width) canvasWidth.value = settings.canvas_width
      if (settings.canvas_height) canvasHeight.value = settings.canvas_height
      if (settings.grid_size) gridSize.value = settings.grid_size
    }
  } catch (e) {
    // No planogram yet — start empty
    if (e?.response?.status !== 404) {
      notify.warning('Gagal memuat planogram: ' + (e.message || 'Terjadi kesalahan'))
    }
    zones.value = []
    planogramItems.value = []
  }
}

async function savePlanogram() {
  saving.value = true
  try {
    const res = await planogramAPI.update(warehouseId.value, {
      canvas_data: {
        zones: zones.value.map((z, i) => ({
          label: z.label,
          x: z.config.x, y: z.config.y,
          width: z.config.width, height: z.config.height,
          color: z.config.fill,
        })),
        items: planogramItems.value.map(item => ({
          product_id: item.product_id,
          product_name: item.product_name,
          x: item.x, y: item.y,
          width: item.width, height: item.height,
          color: item.color,
        })),
      },
      canvas_settings: {
        canvas_width: canvasWidth.value,
        canvas_height: canvasHeight.value,
        grid_size: gridSize.value,
      },
      change_summary: 'Edit planogram',
    })
    currentPlanogram.value = res.data
    lastSaved.value = new Date()
    hasChanges.value = false
    notify.success('Planogram berhasil disimpan')
  } catch (e) {
    notify.error('Gagal menyimpan: ' + (e.message || 'Terjadi kesalahan'))
  } finally {
    saving.value = false
  }
}

async function takeSnapshot() {
  try {
    await planogramAPI.snapshot(warehouseId.value, {
      change_summary: 'Snapshot manual'
    })
    notify.success('Snapshot berhasil disimpan')
  } catch (e) {
    notify.error('Gagal snapshot: ' + (e.message || 'Terjadi kesalahan'))
  }
}

async function searchProducts() {
  clearTimeout(searchTimeout)
  if (productSearch.value.length < 2) {
    searchResults.value = []
    return
  }
  searchTimeout = setTimeout(async () => {
    productSearchLoading.value = true
    try {
      const res = await planogramAPI.searchProduct(productSearch.value)
      searchResults.value = res.data || []
    } catch {
      searchResults.value = []
    } finally {
      productSearchLoading.value = false
    }
  }, 300)
}

function onProductDragStart(event, product) {
  // Store in localStorage so onStageClick can read it (Konva stage can't access dataTransfer)
  localStorage.setItem('_dragProduct', JSON.stringify(product))
  event.dataTransfer.setData('application/json', JSON.stringify(product))
  event.dataTransfer.effectAllowed = 'copy'
}

function onStageClick(e) {
  const stage = stageRef.value?.getStage()
  if (!stage) return
  const pos = stage.getPointerPosition()
  if (activeTool.value === 'item') {
    // Read product stored during drag from sidebar
    const raw = localStorage.getItem('_dragProduct')
    if (raw) {
      try {
        const product = JSON.parse(raw)
        addItemAt(snapToGrid(pos.x), snapToGrid(pos.y), product)
        localStorage.removeItem('_dragProduct')
      } catch {}
    }
  }
}

function onCanvasDrop(e) {
  const raw = e.dataTransfer?.getData('application/json')
  if (!raw) return
  try {
    const product = JSON.parse(raw)
    const stage = stageRef.value?.getStage()
    if (!stage) return
    const pos = stage.getPointerPosition()
    addItemAt(snapToGrid(pos.x), snapToGrid(pos.y), product)
  } catch {}
}

function onStageMouseDown(e) {
  const stage = stageRef.value?.getStage()
  if (!stage) return
  const pos = stage.getPointerPosition()
  if (activeTool.value === 'zone') {
    isDrawing.value = true
    drawStart.value = { x: snapToGrid(pos.x), y: snapToGrid(pos.y) }
    drawingPreview.value = {
      x: drawStart.value.x,
      y: drawStart.value.y,
      width: 0,
      height: 0,
      fill: 'transparent',
      stroke: '#6366f1',
      strokeWidth: 1,
      dash: [4, 4],
    }
  }
}

function onStageMouseMove(e) {
  if (!isDrawing.value) return
  const stage = stageRef.value?.getStage()
  if (!stage) return
  const pos = stage.getPointerPosition()
  const snappedX = snapToGrid(pos.x)
  const snappedY = snapToGrid(pos.y)
  drawingPreview.value = {
    ...drawingPreview.value,
    x: Math.min(drawStart.value.x, snappedX),
    y: Math.min(drawStart.value.y, snappedY),
    width: Math.abs(snappedX - drawStart.value.x),
    height: Math.abs(snappedY - drawStart.value.y),
  }
}

function onStageMouseUp(e) {
  if (!isDrawing.value) return
  isDrawing.value = false
  const w = drawingPreview.value.width
  const h = drawingPreview.value.height
  if (w < 20 || h < 20) {
    drawingPreview.value = {}
    return
  }
  const newZone = {
    label: `Zone ${zones.value.length + 1}`,
    config: {
      x: drawingPreview.value.x,
      y: drawingPreview.value.y,
      width: w,
      height: h,
      fill: ZONE_COLORS[zoneColorIdx % ZONE_COLORS.length],
      stroke: '#6366f1',
      strokeWidth: 1,
      draggable: true,
      cornerRadius: 6,
    }
  }
  zones.value.push(newZone)
  zoneColorIdx++
  drawingPreview.value = {}
  selectedZoneIdx.value = zones.value.length - 1
  selectedItemIdx.value = null
  hasChanges.value = true
  scheduleAutoSave()
}

function selectZone(idx) {
  selectedZoneIdx.value = idx
  selectedItemIdx.value = null
}

function selectItem(idx) {
  selectedItemIdx.value = idx
  selectedZoneIdx.value = null
}

function addItemAt(x, y, product) {
  const item = {
    product_id: product.id,
    product_name: product.name,
    x: x,
    y: y,
    width: 40,
    height: 40,
    color: '#6366f1',
  }
  planogramItems.value.push(item)
  selectedItemIdx.value = planogramItems.value.length - 1
  selectedZoneIdx.value = null
  hasChanges.value = true
  scheduleAutoSave()
}

function onItemDragMove(e) {
  // handled in dragend
}

function onItemDragEnd(idx, e) {
  const node = e.target
  planogramItems.value[idx].x = snapToGrid(node.x())
  planogramItems.value[idx].y = snapToGrid(node.y())
  node.x(planogramItems.value[idx].x)
  node.y(planogramItems.value[idx].y)
  hasChanges.value = true
  scheduleAutoSave()
}

function onZoneDragEnd(idx, e) {
  const node = e.target
  zones.value[idx].config.x = snapToGrid(node.x())
  zones.value[idx].config.y = snapToGrid(node.y())
  node.x(zones.value[idx].config.x)
  node.y(zones.value[idx].config.y)
  hasChanges.value = true
  scheduleAutoSave()
}

function updateItemProp(prop, value) {
  if (planogramItems.value[selectedItemIdx.value]) {
    planogramItems.value[selectedItemIdx.value][prop] = value
    hasChanges.value = true
    scheduleAutoSave()
  }
}

function updateZoneProp(prop, value) {
  if (zones.value[selectedZoneIdx.value]) {
    const keys = prop.split('.')
    if (keys.length === 1) {
      zones.value[selectedZoneIdx.value][prop] = value
    } else {
      zones.value[selectedZoneIdx.value][keys[0]][keys[1]] = value
    }
    hasChanges.value = true
    scheduleAutoSave()
  }
}

function removeItem(idx) {
  planogramItems.value.splice(idx, 1)
  selectedItemIdx.value = null
  hasChanges.value = true
  scheduleAutoSave()
}

function deleteZone(idx) {
  zones.value.splice(idx, 1)
  selectedZoneIdx.value = null
  hasChanges.value = true
  scheduleAutoSave()
}

function onCanvasResize() {
  hasChanges.value = true
  scheduleAutoSave()
}

function scheduleAutoSave() {
  clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(() => {
    if (hasChanges.value) savePlanogram()
  }, 10000)
}

function handleKeydown(e) {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return
  if (e.key === 'Delete' || e.key === 'Backspace') {
    if (selectedItemIdx.value !== null) {
      removeItem(selectedItemIdx.value)
    } else if (selectedZoneIdx.value !== null) {
      deleteZone(selectedZoneIdx.value)
    }
  }
  if (e.key === 'Escape') {
    selectedItemIdx.value = null
    selectedZoneIdx.value = null
  }
  if (e.ctrlKey && e.key === 's') {
    e.preventDefault()
    savePlanogram()
  }
}

function formatTime(date) {
  return format(date, 'HH:mm:ss', { locale: id })
}

onBeforeRouteLeave((to, from, next) => {
  if (hasChanges.value) {
    const answer = window.confirm('Anda memiliki perubahan yang belum disimpan. Simpan sebelum meninggalkan halaman?')
    if (!answer) {
      next(false)
      return
    }
  }
  next()
})

// Handle dragover on canvas for product drop
onMounted(async () => {
  window.addEventListener('keydown', handleKeydown)
  canvasContainer.value?.parentElement?.addEventListener('wheel', handleWheel, { passive: false })

  // Load warehouse + planogram
  try {
    const whRes = await warehouseAPI.show(warehouseId.value)
    warehouse.value = whRes.data || whRes
  } catch {}
  await loadPlanogram()
  setTimeout(zoomFit, 100)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  canvasContainer.value?.parentElement?.removeEventListener('wheel', handleWheel)
  clearTimeout(autoSaveTimer)
  if (hasChanges.value) savePlanogram()
})

function handleWheel(e) {
  if (e.ctrlKey || e.metaKey) {
    e.preventDefault()
    const delta = e.deltaY > 0 ? -0.1 : 0.1
    zoomLevel.value = Math.max(0.25, Math.min(3, zoomLevel.value + delta))
  }
}
</script>
