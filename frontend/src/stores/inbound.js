import { defineStore } from 'pinia'
import { inboundAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useInboundStore = defineStore('inbound', {
  state: () => ({
    inbounds: [],
    selected: null,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 25
    }
  }),

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const res = await inboundAPI.list(params)
        
        if (res.data && Array.isArray(res.data)) {
          this.inbounds = res.data
          this.pagination = {
            current_page: res.meta?.current_page || res.current_page || 1,
            last_page: res.meta?.last_page || res.last_page || 1,
            total: res.meta?.total || res.total || 0,
            per_page: res.meta?.per_page || res.per_page || 25
          }
        } else if (Array.isArray(res)) {
          this.inbounds = res
          this.pagination = { current_page: 1, last_page: 1, total: res.length, per_page: res.length }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat daftar inbound')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id) {
      this.loading = true
      try {
        const res = await inboundAPI.show(id)
        this.selected = res.data || res
        return this.selected
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat detail inbound')
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      try {
        const res = await inboundAPI.create(data)
        const notify = useNotificationStore()
        notify.success('Inbound berhasil dibuat')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal membuat inbound')
        throw error
      }
    },

    async receive(id, data) {
      try {
        const res = await inboundAPI.receive(id, data)
        const notify = useNotificationStore()
        notify.success('Proses penerimaan (Receive) berhasil')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal melakukan penerimaan')
        throw error
      }
    },

    async cancel(id) {
      try {
        const res = await inboundAPI.cancel(id)
        const notify = useNotificationStore()
        notify.success('Inbound dibatalkan')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal membatalkan inbound')
        throw error
      }
    }
  }
})
