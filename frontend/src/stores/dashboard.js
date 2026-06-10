import { defineStore } from 'pinia'
import { dashboardAPI, reportAPI } from '../services/api'
import { useNotificationStore } from './notification'

const getTimeElapsed = (dateString) => {
  if (!dateString) return ''
  const diff = new Date() - new Date(dateString)
  const minutes = Math.floor(diff / 60000)
  if (minutes < 60) return `${minutes} mins ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} hours ago`
  const days = Math.floor(hours / 24)
  return `${days} days ago`
}

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
        const data = res.data?.data || res.data || res
        
        this.metrics = {
          total_products: data.total_sku || 0,
          inbound_today: data.today_inbounds || 0,
          outbound_today: data.today_outbounds || 0,
          low_stock: data.low_stock_alerts || 0,
          total_stock_value: data.total_stock_value || 0,
          near_expiry: data.near_expiry_alerts || 0
        }

        const transactions = data.recent_transactions || []
        this.recentActivity = transactions.map(tx => {
          let iconColor = 'text-gray-500 bg-gray-100'
          let parsedType = 'other'
          const rawType = (tx.type || '').toUpperCase()
          
          if (rawType === 'GR' || rawType === 'INBOUND') {
            iconColor = 'text-green-500 bg-green-100'
            parsedType = 'inbound'
          } else if (rawType === 'GI' || rawType === 'OUTBOUND') {
            iconColor = 'text-blue-500 bg-blue-100'
            parsedType = 'outbound'
          } else if (rawType === 'TR' || rawType === 'TRANSFER') {
            iconColor = 'text-purple-500 bg-purple-100'
            parsedType = 'transfer'
          }
          
          let descText = tx.description ? `${tx.description} (${Number(tx.quantity)} unit)` : `${Number(tx.quantity)} unit`
          if (tx.user) {
            descText += ` oleh ${tx.user}`
          }

          return {
            id: tx.id,
            title: `${rawType} #${tx.reference || '-'}`,
            desc: descText,
            time: getTimeElapsed(tx.created_at),
            type: parsedType,
            iconColor
          }
        })
      } catch (error) {
        console.error('Failed to fetch dashboard metrics:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchReports() {
      try {
        const valRes = await reportAPI.valuation()
        this.stockValuation = valRes.data?.data || valRes.data || valRes

        const utilRes = await reportAPI.warehouseUtilization()
        const utilData = utilRes.data?.data || utilRes.data || utilRes || []
        
        this.warehouseUtilization = Array.isArray(utilData) ? utilData.map(item => ({
          warehouse_name: item.warehouse?.name || 'Unknown',
          used_capacity: item.utilization || 0,
          total_capacity: item.total_slots || 0
        })) : []
      } catch (error) {
        console.error('Failed to fetch dashboard reports:', error)
      }
    }
  }
})
