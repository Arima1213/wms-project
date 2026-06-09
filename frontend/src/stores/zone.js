import { defineStore } from 'pinia'
import { zoneAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useZoneStore = defineStore('zone', {
  state: () => ({ zones: [], loading: false }),
  actions: {
    async fetchList(warehouseId) {
      if (!warehouseId) { this.zones = []; return }
      this.loading = true
      try {
        const res = await zoneAPI.list(warehouseId)
        this.zones = Array.isArray(res) ? res : (res.data || [])
      } catch (e) {
        console.error('[ZoneStore] fetchList error:', e.response?.data || e.message)
        this.zones = []
      } finally {
        this.loading = false
      }
    },
    async create(warehouseId, data) {
      const notify = useNotificationStore()
      try {
        await zoneAPI.create(warehouseId, data)
        notify.success('Zona berhasil ditambahkan')
      } catch (e) {
        notify.error(e.response?.data?.message || 'Gagal menambah zona')
        throw e
      }
    },
    async update(warehouseId, id, data) {
      const notify = useNotificationStore()
      try {
        await zoneAPI.update(warehouseId, id, data)
        notify.success('Zona berhasil diperbarui')
      } catch (e) {
        notify.error(e.response?.data?.message || 'Gagal memperbarui zona')
        throw e
      }
    },
    async remove(warehouseId, id) {
      const notify = useNotificationStore()
      try {
        await zoneAPI.delete(warehouseId, id)
        notify.success('Zona berhasil dihapus')
      } catch (e) {
        notify.error(e.response?.data?.message || 'Gagal menghapus zona')
        throw e
      }
    },
    async toggleActive(id, activate) {
      const notify = useNotificationStore()
      try {
        if (activate) await zoneAPI.activate(id)
        else await zoneAPI.deactivate(id)
        notify.success(activate ? 'Zona diaktifkan' : 'Zona dinonaktifkan')
      } catch (e) {
        notify.error(e.response?.data?.message || 'Gagal mengubah status')
        throw e
      }
    }
  }
})
