import { defineStore } from 'pinia'
import api from '../services/api'

export const useBinStore = defineStore('bin', {
  state: () => ({
    items: [],
    item: null,
    loading: false,
    pagination: { current: 1, last: 1 },
    filters: { rack_id: '', warehouse_id: '', bin_type: '', is_active: '', search: '' },
  }),

  actions: {
    async fetchBins(params = {}) {
      this.loading = true
      try {
        const query = { ...this.filters, ...params }
        Object.keys(query).forEach(k => { if (!query[k]) delete query[k] })
        const res = await api.get('/bins', { params: query })
        this.items = res.data.data || res.data
        if (res.data.meta) {
          this.pagination = {
            current: res.data.meta.current_page,
            last: res.data.meta.last_page,
          }
        }
      } finally {
        this.loading = false
      }
    },

    async fetchBin(id) {
      this.loading = true
      try {
        const res = await api.get(`/bins/${id}`)
        this.item = res.data.data || res.data
        return this.item
      } finally {
        this.loading = false
      }
    },

    async createBin(data) {
      const res = await api.post('/bins', data)
      return res.data.data || res.data
    },

    async updateBin(id, data) {
      const res = await api.put(`/bins/${id}`, data)
      return res.data.data || res.data
    },

    async deleteBin(id) {
      await api.delete(`/bins/${id}`)
    },

    async toggleActive(id) {
      const res = await api.post(`/bins/${id}/toggle-active`)
      return res.data.data || res.data
    },

    async getOccupancy(id) {
      const res = await api.get(`/bins/${id}/occupancy`)
      return res.data
    },
  },
})
