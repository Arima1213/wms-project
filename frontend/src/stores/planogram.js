import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { planogramAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const usePlanogramStore = defineStore('planogram', () => {
  const notify = useNotificationStore()

  // State
  const planogram = ref(null)
  const zones = ref([])
  const items = ref([])
  const selectedRackIdx = ref(null)
  const selectedItemIdx = ref(null)
  const editMode = ref(false)
  const zoom = ref(1)
  const panOffset = ref({ x: 0, y: 0 })
  const loading = ref(false)
  const saving = ref(false)
  const lastSaved = ref(null)

  // Undo/redo
  const history = ref([])
  const historyIndex = ref(-1)
  const maxHistory = 50

  const canUndo = computed(() => historyIndex.value > 0)
  const canRedo = computed(() => historyIndex.value < history.value.length - 1)
  const hasChanges = computed(() => historyIndex.value !== (history.value.length > 0 ? 0 : -1))

  function pushHistory() {
    const snapshot = JSON.stringify({ zones: zones.value, items: items.value })
    // Remove any future history entries if we're not at the end
    if (historyIndex.value < history.value.length - 1) {
      history.value = history.value.slice(0, historyIndex.value + 1)
    }
    history.value.push(snapshot)
    if (history.value.length > maxHistory) {
      history.value.shift()
    }
    historyIndex.value = history.value.length - 1
  }

  function undo() {
    if (!canUndo.value) return
    historyIndex.value--
    const snapshot = JSON.parse(history.value[historyIndex.value])
    zones.value = snapshot.zones
    items.value = snapshot.items
  }

  function redo() {
    if (!canRedo.value) return
    historyIndex.value++
    const snapshot = JSON.parse(history.value[historyIndex.value])
    zones.value = snapshot.zones
    items.value = snapshot.items
  }

  async function loadPlanogram(warehouseId) {
    loading.value = true
    try {
      const res = await planogramAPI.show(warehouseId)
      const pg = res.data
      planogram.value = pg
      if (pg?.canvas_data) {
        zones.value = pg.canvas_data.zones || []
        items.value = pg.canvas_data.items || []
      } else {
        zones.value = []
        items.value = []
      }
      // Initialize history
      history.value = []
      historyIndex.value = -1
      pushHistory()
    } catch {
      zones.value = []
      items.value = []
      history.value = []
      historyIndex.value = -1
      pushHistory()
    } finally {
      loading.value = false
    }
  }

  async function savePlanogram(warehouseId, canvasSettings = {}) {
    saving.value = true
    try {
      const res = await planogramAPI.update(warehouseId, {
        canvas_data: { zones: zones.value, items: items.value },
        canvas_settings: canvasSettings,
        change_summary: 'Edit planogram',
      })
      planogram.value = res.data
      lastSaved.value = new Date()
      notify.success('Planogram berhasil disimpan')
    } catch (e) {
      notify.error('Gagal menyimpan planogram')
      throw e
    } finally {
      saving.value = false
    }
  }

  function addZone(zone) {
    zones.value.push(zone)
    pushHistory()
  }

  function removeZone(idx) {
    zones.value.splice(idx, 1)
    selectedRackIdx.value = null
    pushHistory()
  }

  function addItem(item) {
    items.value.push(item)
    selectedItemIdx.value = items.value.length - 1
    pushHistory()
  }

  function removeItem(idx) {
    items.value.splice(idx, 1)
    selectedItemIdx.value = null
    pushHistory()
  }

  function reset() {
    planogram.value = null
    zones.value = []
    items.value = []
    selectedRackIdx.value = null
    selectedItemIdx.value = null
    history.value = []
    historyIndex.value = -1
  }

  return {
    planogram, zones, items,
    selectedRackIdx, selectedItemIdx, editMode,
    zoom, panOffset, loading, saving, lastSaved,
    canUndo, canRedo, hasChanges,
    pushHistory, undo, redo,
    loadPlanogram, savePlanogram,
    addZone, removeZone, addItem, removeItem, reset,
  }
})
