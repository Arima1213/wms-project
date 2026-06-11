import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { warehouseAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useWarehouseStore = defineStore('warehouse', () => {
  const notify = useNotificationStore()

  const warehouses = ref([])
  const selected = ref(null)
  const loading = ref(false)
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })

  const activeWarehouses = computed(() => warehouses.value.filter(w => w.is_active))

  async function fetchList(params = {}) {
    loading.value = true
    try {
      const res = await warehouseAPI.list(params)
      if (Array.isArray(res)) {
        warehouses.value = res
        pagination.value = { current_page: 1, last_page: 1, total: res.length, from: 1, to: res.length }
      } else {
        warehouses.value = res.data || []
        pagination.value = {
          current_page: res.current_page || 1,
          last_page: res.last_page || 1,
          total: res.total || 0,
          from: res.from || 0,
          to: res.to || 0,
        }
      }
    } catch (e) {
      notify.error(e.response?.data?.message || 'Gagal memuat gudang')
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    try {
      const res = await warehouseAPI.show(id)
      selected.value = res.data || res
      return selected.value
    } catch (e) {
      notify.error('Gagal memuat detail gudang')
      return null
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    try {
      const res = await warehouseAPI.create(data)
      notify.success('Gudang berhasil ditambahkan')
      await fetchList()
      return res.data || res
    } catch (e) {
      notify.error(e.response?.data?.message || 'Gagal menambah gudang')
      throw e
    }
  }

  async function update(id, data) {
    try {
      const res = await warehouseAPI.update(id, data)
      notify.success('Gudang berhasil diperbarui')
      await fetchList()
      return res.data || res
    } catch (e) {
      notify.error(e.response?.data?.message || 'Gagal memperbarui gudang')
      throw e
    }
  }

  async function remove(id) {
    try {
      await warehouseAPI.delete(id)
      notify.success('Gudang berhasil dihapus')
      await fetchList()
    } catch (e) {
      notify.error(e.response?.data?.message || 'Gagal menghapus gudang')
      throw e
    }
  }

  function clearSelected() {
    selected.value = null
  }

  return {
    warehouses, selected, loading, pagination,
    activeWarehouses,
    fetchList, fetchOne, create, update, remove, clearSelected,
  }
})
