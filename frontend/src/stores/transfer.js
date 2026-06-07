import { defineStore } from 'pinia'
import { transferAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useTransferStore = defineStore('transfer', {
  state: () => ({
    transfers: [],
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
        const res = await transferAPI.list(params)
        
        if (res.data && Array.isArray(res.data)) {
          this.transfers = res.data
          this.pagination = {
            current_page: res.meta?.current_page || res.current_page || 1,
            last_page: res.meta?.last_page || res.last_page || 1,
            total: res.meta?.total || res.total || 0,
            per_page: res.meta?.per_page || res.per_page || 25
          }
        } else if (Array.isArray(res)) {
          this.transfers = res
          this.pagination = { current_page: 1, last_page: 1, total: res.length, per_page: res.length }
        } else if (res.data && res.data.data) {
          this.transfers = res.data.data
          this.pagination = {
            current_page: res.data.current_page || 1,
            last_page: res.data.last_page || 1,
            total: res.data.total || 0,
            per_page: res.data.per_page || 25
          }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat daftar transfer')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id) {
      this.loading = true
      try {
        const res = await transferAPI.show(id)
        this.selected = res.data || res
        return this.selected
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat detail transfer')
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      try {
        const res = await transferAPI.create(data)
        const notify = useNotificationStore()
        notify.success('Pengajuan transfer berhasil dibuat')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal membuat transfer')
        throw error
      }
    },

    async approve(id) {
      try {
        const res = await transferAPI.approve(id)
        const notify = useNotificationStore()
        notify.success('Transfer disetujui')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menyetujui transfer')
        throw error
      }
    },

    async reject(id) {
      try {
        const res = await transferAPI.reject(id)
        const notify = useNotificationStore()
        notify.success('Transfer ditolak')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menolak transfer')
        throw error
      }
    },

    async execute(id) {
      try {
        const res = await transferAPI.execute(id)
        const notify = useNotificationStore()
        notify.success('Transfer berhasil dieksekusi')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal mengeksekusi transfer')
        throw error
      }
    }
  }
})
