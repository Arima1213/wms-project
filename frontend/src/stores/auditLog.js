import { defineStore } from 'pinia'
import { auditAPI } from '../services/api'

export const useAuditLogStore = defineStore('auditLog', {
  state: () => ({
    items: [],
    loading: false,
    pagination: { current_page: 1, last_page: 1, total: 0, per_page: 50 }
  }),
  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const res = await auditAPI.list({
          page: params.page || 1,
          per_page: params.per_page || 50,
          ...(params.entity_type ? { entity_type: params.entity_type } : {}),
          ...(params.from ? { from: params.from } : {}),
          ...(params.to ? { to: params.to } : {}),
        })
        this.items = res.data || []
        this.pagination = {
          current_page: res.current_page || 1,
          last_page: res.last_page || 1,
          total: res.total || 0,
          per_page: res.per_page || 50
        }
      } catch (e) {
        this.items = []
      } finally {
        this.loading = false
      }
    }
  }
})
