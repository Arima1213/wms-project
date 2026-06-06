import { defineStore } from 'pinia'
import { productAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useProductStore = defineStore('product', {
  state: () => ({
    products: [],
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
        const res = await productAPI.list(params)
        
        // Handle Laravel pagination format
        if (res.data && Array.isArray(res.data)) {
          this.products = res.data
          this.pagination = {
            current_page: res.meta?.current_page || res.current_page || 1,
            last_page: res.meta?.last_page || res.last_page || 1,
            total: res.meta?.total || res.total || 0,
            per_page: res.meta?.per_page || res.per_page || 25
          }
        } else if (Array.isArray(res)) {
          this.products = res
          this.pagination = { current_page: 1, last_page: 1, total: res.length, per_page: res.length }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat daftar produk')
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id) {
      this.loading = true
      try {
        const res = await productAPI.show(id)
        this.selected = res.data || res
        return this.selected
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat detail produk')
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      try {
        const res = await productAPI.create(data)
        const notify = useNotificationStore()
        notify.success('Produk berhasil ditambahkan')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menambahkan produk')
        throw error
      }
    },

    async update(id, data) {
      try {
        const res = await productAPI.update(id, data)
        const notify = useNotificationStore()
        notify.success('Produk berhasil diperbarui')
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal memperbarui produk')
        throw error
      }
    },

    async remove(id) {
      try {
        await productAPI.delete(id)
        const notify = useNotificationStore()
        notify.success('Produk berhasil dihapus')
        this.products = this.products.filter(p => p.id !== id)
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menghapus produk')
        throw error
      }
    }
  }
})
