import { defineStore } from 'pinia'
import { outboundAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useOutboundStore = defineStore('outbound', {
  state: () => ({
    outbounds: [],
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
        const res = await outboundAPI.list(params)
        
        if (res.data && Array.isArray(res.data)) {
          this.outbounds = res.data
          this.pagination = {
            current_page: res.meta?.current_page || res.current_page || 1,
            last_page: res.meta?.last_page || res.last_page || 1,
            total: res.meta?.total || res.total || 0,
            per_page: res.meta?.per_page || res.per_page || 25
          }
        } else if (Array.isArray(res)) {
          this.outbounds = res
          this.pagination = { current_page: 1, last_page: 1, total: res.length, per_page: res.length }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat daftar outbound')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id) {
      this.loading = true
      try {
        const res = await outboundAPI.show(id)
        this.selected = res.data || res
        return this.selected
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat detail outbound')
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      try {
        const res = await outboundAPI.create(data)
        const notify = useNotificationStore()
        notify.success('Outbound berhasil dibuat')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal membuat outbound')
        throw error
      }
    },

    async ship(id, data) {
      try {
        const res = await outboundAPI.ship(id, data)
        const notify = useNotificationStore()
        notify.success('Proses pengiriman (Ship) berhasil')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal melakukan pengiriman')
        throw error
      }
    }
  }
})
