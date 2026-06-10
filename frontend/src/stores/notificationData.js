import { defineStore } from 'pinia'
import { notificationAPI } from '../services/api'

export const useNotificationDataStore = defineStore('notificationData', {
  state: () => ({
    items: [],
    unreadCount: 0,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    },
  }),

  actions: {
    async fetchNotifications(page = 1) {
      this.loading = true
      try {
        const res = await notificationAPI.index({ per_page: 20, page })
        const data = res.data?.data || res.data || res
        this.items = Array.isArray(data) ? data : (data.data || [])
        if (data.meta) {
          this.pagination = data.meta
        }
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchUnreadCount() {
      try {
        const res = await notificationAPI.unreadCount()
        const data = res.data?.data || res.data || res
        this.unreadCount = data.count || 0
      } catch (error) {
        console.error('Failed to fetch unread count:', error)
      }
    },

    async markAsRead(id) {
      try {
        await notificationAPI.markRead(id)
        const notif = this.items.find(n => n.id === id)
        if (notif) notif.read_at = new Date().toISOString()
        this.unreadCount = Math.max(0, this.unreadCount - 1)
      } catch (error) {
        console.error('Failed to mark notification as read:', error)
      }
    },

    async markAllAsRead() {
      try {
        await notificationAPI.markAllRead()
        this.items.forEach(n => {
          if (!n.read_at) n.read_at = new Date().toISOString()
        })
        this.unreadCount = 0
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error)
      }
    },
  },
})
