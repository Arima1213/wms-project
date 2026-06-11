import { defineStore } from 'pinia'
import { categoryAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useCategoryStore = defineStore('category', {
  state: () => ({
    categories: [],
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
        const res = await categoryAPI.list(params)
        if (Array.isArray(res)) {
          this.categories = res
        } else if (res.data) {
          this.categories = res.data
          this.pagination = {
            current_page: res.current_page || 1,
            last_page: res.last_page || 1,
            total: res.total || 0,
            per_page: res.per_page || 25
          }
        }
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat kategori')
        throw error
      } finally {
        this.loading = false
      }
    },

    async create(data) {
      try {
        const res = await categoryAPI.create(data)
        const notify = useNotificationStore()
        notify.success('Kategori berhasil ditambahkan')
        await this.fetchList()
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menambah kategori')
        throw error
      }
    },

    async update(id, data) {
      try {
        const res = await categoryAPI.update(id, data)
        const notify = useNotificationStore()
        notify.success('Kategori berhasil diperbarui')
        await this.fetchList()
        return res
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal memperbarui kategori')
        throw error
      }
    },

    async remove(id) {
      try {
        await categoryAPI.delete(id)
        const notify = useNotificationStore()
        notify.success('Kategori berhasil dihapus')
        await this.fetchList()
      } catch (error) {
        const notify = useNotificationStore()
        notify.error(error.response?.data?.message || 'Gagal menghapus kategori')
        throw error
      }
    }
  }
})
