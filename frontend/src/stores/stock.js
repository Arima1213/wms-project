import { defineStore } from 'pinia'
import { inventoryAPI, reportAPI, stockOpnameAPI } from '../services/api'
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
        const res = await inventoryAPI.index()
        this.summary = res.data || res
      } catch (error) {
        console.error('Error fetching stock summary', error)
      }
    },

    async fetchLowStock() {
      try {
        const res = await inventoryAPI.alerts()
        this.lowStock = res.data || res
      } catch (error) {
        console.error('Error fetching low stock', error)
      }
    },

    async transfer(data) {
      try {
        // Fallback or skip since transfer endpoint isn't defined explicitly in inventory API yet, maybe it's in another module
        throw new Error('Transfer logic uses stock-opnames or transfer module')
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.message || 'Gagal membuat transfer stok')
        throw error
      }
    },

    async adjust(data) {
      try {
        // Adjust uses stock opname API in our case
        const res = await stockOpnameAPI.create(data)
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
