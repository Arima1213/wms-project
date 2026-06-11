import { defineStore } from 'pinia'
import { rackSlotAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useRackSlotStore = defineStore('rackSlot', {
  state: () => ({ slots: [], loading: false }),
  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const res = await rackSlotAPI.list(params)
        this.slots = res.data || []
      } catch (e) {
        this.slots = []
      } finally {
        this.loading = false
      }
    },
    async create(data) {
      const notify = useNotificationStore()
      try { await rackSlotAPI.create(data); notify.success('Slot berhasil ditambahkan'); await this.fetchList() }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async update(id, data) {
      const notify = useNotificationStore()
      try { await rackSlotAPI.update(id, data); notify.success('Slot berhasil diperbarui'); await this.fetchList() }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async remove(id) {
      const notify = useNotificationStore()
      try { await rackSlotAPI.delete(id); notify.success('Slot berhasil dihapus'); await this.fetchList() }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async assign(id, data) {
      const notify = useNotificationStore()
      try { await rackSlotAPI.assign(id, data); notify.success('Produk ditetapkan ke slot'); await this.fetchList() }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async unassign(id) {
      const notify = useNotificationStore()
      try { await rackSlotAPI.unassign(id); notify.success('Produk dilepas dari slot'); await this.fetchList() }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    },
    async reserve(id, data) {
      const notify = useNotificationStore()
      try { await rackSlotAPI.reserve(id, data); notify.success('Slot direservasi'); await this.fetchList() }
      catch (e) { notify.error(e.response?.data?.message || 'Gagal'); throw e }
    }
  }
})
