import { defineStore } from 'pinia'
import { stockOpnameAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useStockOpnameStore = defineStore('stockOpname', {
  state: () => ({
    opnames: [],
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
        const res = await stockOpnameAPI.list(params)
        
        if (res.data && Array.isArray(res.data)) {
          this.opnames = res.data
          this.pagination = {
            current_page: res.meta?.current_page || res.current_page || 1,
            last_page: res.meta?.last_page || res.last_page || 1,
            total: res.meta?.total || res.total || 0,
            per_page: res.meta?.per_page || res.per_page || 25
          }
        } else if (Array.isArray(res)) {
          this.opnames = res
          this.pagination = { current_page: 1, last_page: 1, total: res.length, per_page: res.length }
        } else if (res.data && res.data.data) {
          // Laravel standard pagination format
          this.opnames = res.data.data
          this.pagination = {
            current_page: res.data.current_page || 1,
            last_page: res.data.last_page || 1,
            total: res.data.total || 0,
            per_page: res.data.per_page || 25
          }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat daftar stock opname')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id) {
      this.loading = true
      try {
        const res = await stockOpnameAPI.show(id)
        this.selected = res.data || res
        return this.selected
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat detail stock opname')
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      try {
        const res = await stockOpnameAPI.create(data)
        const notify = useNotificationStore()
        notify.success('Stock Opname berhasil dibuat')
        await this.fetchList()
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal membuat stock opname')
        throw error
      }
    },

    async submit(id, data) {
      try {
        const res = await stockOpnameAPI.submit(id, data)
        const notify = useNotificationStore()
        notify.success('Stock Opname berhasil disubmit')
        await this.fetchList()
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal mensubmit stock opname')
        throw error
      }
    },

    async approve(id, data) {
      try {
        const res = await stockOpnameAPI.approve(id, data)
        const notify = useNotificationStore()
        notify.success('Stock Opname berhasil disetujui')
        await this.fetchList()
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menyetujui stock opname')
        throw error
      }
    },

    clearSelected() {
      this.selected = null
    }
  }
})
