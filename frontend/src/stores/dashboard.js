import { defineStore } from 'pinia'
import { dashboardAPI, reportAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    metrics: null,
    stockValuation: null,
    warehouseUtilization: [],
    recentActivity: [],
    loading: false
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true
      try {
        const res = await dashboardAPI.index()
        const data = res.data || res
        this.metrics = data.metrics || null
        this.recentActivity = data.recent_activity || []
      } catch (error) {
        console.error('Failed to fetch dashboard metrics:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchReports() {
      try {
        const valRes = await reportAPI.valuation()
        this.stockValuation = valRes.data || valRes

        const utilRes = await reportAPI.warehouseUtilization()
        this.warehouseUtilization = utilRes.data || utilRes || []
      } catch (error) {
        console.error('Failed to fetch dashboard reports:', error)
      }
    }
  }
})
