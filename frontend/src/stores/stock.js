import { defineStore } from 'pinia'
import { stockAPI, reportAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useStockStore = defineStore('stock', {
  state: () => ({
    stocks: [],
    summary: null,
    lowStock: [],
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
        const res = await reportAPI.stock(params)
        
        if (res.data && Array.isArray(res.data)) {
          this.stocks = res.data
          this.pagination = {
            current_page: res.meta?.current_page || res.current_page || 1,
            last_page: res.meta?.last_page || res.last_page || 1,
            total: res.meta?.total || res.total || 0,
            per_page: res.meta?.per_page || res.per_page || 25
          }
        } else if (Array.isArray(res)) {
          this.stocks = res
          this.pagination = { current_page: 1, last_page: 1, total: res.length, per_page: res.length }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat daftar stok')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchSummary() {
      try {
        const res = await stockAPI.summary()
        this.summary = res.data || res
      } catch (error) {
        console.error('Error fetching stock summary', error)
      }
    },

    async fetchLowStock() {
      try {
        const res = await stockAPI.lowStock()
        this.lowStock = res.data || res
      } catch (error) {
        console.error('Error fetching low stock', error)
      }
    },

    async transfer(data) {
      try {
        const res = await stockAPI.transfer(data)
        const notify = useNotificationStore()
        notify.success('Transfer stok berhasil dibuat')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal membuat transfer stok')
        throw error
      }
    },

    async adjust(data) {
      try {
        const res = await stockAPI.adjust(data)
        const notify = useNotificationStore()
        notify.success('Penyesuaian stok berhasil disimpan')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menyimpan penyesuaian stok')
        throw error
      }
    }
  }
})
