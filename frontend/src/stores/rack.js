import { defineStore } from 'pinia'
import { rackAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useRackStore = defineStore('rack', {
  state: () => ({ racks: [], loading: false }),
  actions: {
    async fetchList(zoneId, params = {}) {
      if (!zoneId) { this.racks = []; return }
      this.loading = true
      try {
        const res = await rackAPI.list(zoneId, params)
        this.racks = res.data || []
      } catch (e) {
        this.racks = []
      } finally {
        this.loading = false
      }
    },
    async create(zoneId, data) {
      const notify = useNotificationStore()
      try { await rackAPI.create(zoneId, data); notify.success('Rak berhasil ditambahkan'); await this.fetchList(zoneId) }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async update(zoneId, id, data) {
      const notify = useNotificationStore()
      try { await rackAPI.update(zoneId, id, data); notify.success('Rak berhasil diperbarui'); await this.fetchList(zoneId) }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async remove(zoneId, id) {
      const notify = useNotificationStore()
      try { await rackAPI.delete(zoneId, id); notify.success('Rak berhasil dihapus'); await this.fetchList(zoneId) }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    }
  }
})
