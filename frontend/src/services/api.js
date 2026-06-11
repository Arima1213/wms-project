import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

// Notification helper
let notify = null
const getNotify = () => {
  if (!notify) {
    // Dynamic import to avoid circular dependencies and pinia init issues
    import('../stores/notification').then((module) => {
      notify = module.useNotificationStore()
    })
  }
  return notify
}

// Pre-initialize
getNotify()

// ============================
// CSRF Refresh
// ============================
// Track agar tidak refresh CSRF berkali-kali dalam waktu singkat
let lastCsrfRefresh = 0
const CSRF_REFRESH_INTERVAL = 30 * 60 * 1000 // 30 menit

// Request interceptor — refresh CSRF cookie sebelum mutation
api.interceptors.request.use(async (config) => {
  if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
    const now = Date.now()
    if (now - lastCsrfRefresh > CSRF_REFRESH_INTERVAL) {
      try {
        await csrfAPI.get('/sanctum/csrf-cookie')
        lastCsrfRefresh = now
      } catch {}
    }
  }
  return config
}, (error) => Promise.reject(error))

// Response interceptor
let isRedirectingToLogin = false

api.interceptors.response.use(
  (response) => {
    const method = response.config.method?.toLowerCase()
    // Auto-success for mutations
    if (['post', 'put', 'patch', 'delete'].includes(method)) {
      if (!response.config.url.includes('/snapshot') && !response.config.url.includes('/login')) {
        const store = getNotify()
        if (store) store.success(response.data?.message || 'Proses berhasil diselesaikan')
      }
    }
    return response.data
  },
  (error) => {
    const status = error.response?.status
    const url = window.location.pathname

    if (status === 401 && !url.includes('/login') && !isRedirectingToLogin) {
      isRedirectingToLogin = true
      const store = getNotify()
      if (store) {
        store.error('Sesi kamu telah habis. Mengarahkan ke halaman login...')
      }
      setTimeout(() => {
        window.location.href = '/login'
      }, 1500)
    } else if (status === 419) {
      // CSRF token mismatch — refresh CSRF cookie silently and retry
      return csrfAPI.get('/sanctum/csrf-cookie').then(() => {
        const config = error.config
        config.headers['X-XSRF-TOKEN'] = ''
        return api(config)
      })
    } else if (status !== 401) {
      const store = getNotify()
      if (store) {
        const msg = error.response?.data?.message || 'Terjadi kesalahan sistem, silakan coba lagi'
        store.error(msg)
      }
    }
    return Promise.reject(error)
  }
)

export default api

// CSRF-specific axios — uses Vite proxy so it's same-origin (no CORS issues)
// Vite config proxies /sanctum/* to the backend server
export const csrfAPI = axios.create({
  baseURL: '',
  timeout: 30000,
  headers: { 'Content-Type': 'application/json' },
  withCredentials: true,
})

// Auth
export const authAPI = {
  login: (data) => api.post('/login', data),
  register: (data) => api.post('/register', data),
  logout: () => api.post('/logout'),
  me: () => api.get('/me'),
}

// Warehouses
export const warehouseAPI = {
  list: (params) => api.get('/warehouses', { params }),
  show: (uuid) => api.get(`/warehouses/${uuid}`),
  create: (data) => api.post('/warehouses', data),
  update: (uuid, data) => api.put(`/warehouses/${uuid}`, data),
  delete: (uuid) => api.delete(`/warehouses/${uuid}`),
}

// Products
export const productAPI = {
  list: (params) => api.get('/products', { params }),
  show: (uuid) => api.get(`/products/${uuid}`),
  create: (data) => api.post('/products', data),
  update: (uuid, data) => api.put(`/products/${uuid}`, data),
  delete: (uuid) => api.delete(`/products/${uuid}`),
  search: (q) => api.get('/products/search', { params: { q } }),
}

// Categories
export const categoryAPI = {
  list: (params) => api.get('/categories', { params }),
  show: (id) => api.get(`/categories/${id}`),
  create: (data) => api.post('/categories', data),
  update: (id, data) => api.put(`/categories/${id}`, data),
  delete: (id) => api.delete(`/categories/${id}`),
}

// Inventory
export const inventoryAPI = {
  index: (params) => api.get('/inventory', { params }),
  stock: (params) => api.get('/inventory/stock', { params }),
  alerts: (params) => api.get('/inventory/alerts', { params }),
  trace: (sku) => api.get(`/inventory/trace/${sku}`),
}

// Inbounds
export const inboundAPI = {
  list: (params) => api.get('/inbounds', { params }),
  show: (uuid) => api.get(`/inbounds/${uuid}`),
  create: (data) => api.post('/inbounds', data),
  update: (uuid, data) => api.put(`/inbounds/${uuid}`, data),
  receive: (uuid, data) => api.post(`/inbounds/${uuid}/receive`, data),
  cancel: (uuid, data) => api.post(`/inbounds/${uuid}/cancel`, data),
}

// Outbounds
export const outboundAPI = {
  list: (params) => api.get('/outbounds', { params }),
  show: (uuid) => api.get(`/outbounds/${uuid}`),
  create: (data) => api.post('/outbounds', data),
  update: (uuid, data) => api.put(`/outbounds/${uuid}`, data),
  pick: (uuid, data) => api.post(`/outbounds/${uuid}/pick`, data),
  ship: (uuid, data) => api.post(`/outbounds/${uuid}/ship`, data),
  cancel: (uuid, data) => api.post(`/outbounds/${uuid}/cancel`, data),
}

// Stock Opnames
export const stockOpnameAPI = {
  list: (params) => api.get('/stock-opnames', { params }),
  show: (uuid) => api.get(`/stock-opnames/${uuid}`),
  create: (data) => api.post('/stock-opnames', data),
  update: (uuid, data) => api.put(`/stock-opnames/${uuid}`, data),
  start: (uuid) => api.post(`/stock-opnames/${uuid}/start`),
  submit: (uuid, data) => api.post(`/stock-opnames/${uuid}/submit`, data),
  approve: (uuid, data) => api.post(`/stock-opnames/${uuid}/approve`, data),
}

// Transfers
export const transferAPI = {
  list: (params) => api.get('/transfers', { params }),
  show: (uuid) => api.get(`/transfers/${uuid}`),
  create: (data) => api.post('/transfers', data),
  approve: (uuid) => api.post(`/transfers/${uuid}/approve`),
  reject: (uuid) => api.post(`/transfers/${uuid}/reject`),
  execute: (uuid) => api.post(`/transfers/${uuid}/execute`),
}

// Planograms
export const planogramAPI = {
  // GET /v1/warehouses/{id}/planogram
  show: (warehouseId) => api.get(`/warehouses/${warehouseId}/planogram`),
  // PUT /v1/warehouses/{id}/planogram
  update: (warehouseId, data) => api.put(`/warehouses/${warehouseId}/planogram`, data),
  // POST /v1/warehouses/{id}/planogram/snapshot
  snapshot: (warehouseId, data) => api.post(`/warehouses/${warehouseId}/planogram/snapshot`, data),
  // GET /v1/warehouses/{id}/planogram/history
  history: (warehouseId) => api.get(`/warehouses/${warehouseId}/planogram/history`),
  // GET /v1/planogram/search?q=
  searchProduct: (q) => api.get('/planogram/search', { params: { q } }),
}

// Dashboard
export const dashboardAPI = {
  index: () => api.get('/dashboard'),
}

// Reports
export const reportAPI = {
  stock: (params) => api.get('/reports/stock', { params }),
  movement: (params) => api.get('/reports/mutations', { params }),
  valuation: (params) => api.get('/reports/valuation', { params }),
  warehouseUtilization: (params) => api.get('/reports/utilization', { params }),
  aging: (params) => api.get('/reports/aging', { params }),
  expiry: (params) => api.get('/reports/expiry', { params }),
  activity: (params) => api.get('/reports/activity', { params }),
  export: (data) => api.post('/reports/export', data, { responseType: 'blob' }),
}

// Users & RBAC
export const userAPI = {
  list: (params) => api.get('/users', { params }),
  show: (id) => api.get(`/users/${id}`),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
  roles: () => api.get('/roles'),
  permissions: () => api.get('/permissions'),
}

// Settings
export const settingAPI = {
  index: () => api.get('/settings'),
  update: (data) => api.put('/settings', data),
}

// Audit Logs
export const auditAPI = {
  list: (params) => api.get('/audit-logs', { params }),
}

// Notifications
export const notificationAPI = {
  index: (params) => api.get('/notifications', { params }),
  unreadCount: () => api.get('/notifications/unread-count'),
  markRead: (id) => api.put(`/notifications/${id}/read`),
  markAllRead: () => api.put('/notifications/read-all'),
}

// Documents
export const documentAPI = {
  list: (params) => api.get('/documents', { params }),
  show: (id) => api.get(`/documents/${id}`),
  upload: (data) => api.post('/documents/upload', data, { headers: { 'Content-Type': 'multipart/form-data' } }),
  delete: (id) => api.delete(`/documents/${id}`),
}

// Zones
export const zoneAPI = {
  list: (warehouseId, params) => api.get(`/warehouses/${warehouseId}/zones`, { params }),
  show: (warehouseId, id) => api.get(`/warehouses/${warehouseId}/zones/${id}`),
  create: (warehouseId, data) => api.post(`/warehouses/${warehouseId}/zones`, data),
  update: (warehouseId, id, data) => api.put(`/warehouses/${warehouseId}/zones/${id}`, data),
  delete: (warehouseId, id) => api.delete(`/warehouses/${warehouseId}/zones/${id}`),
  activate: (id) => api.put(`/zones/${id}/activate`),
  deactivate: (id) => api.put(`/zones/${id}/deactivate`),
}

// Racks (nested under zones)
export const rackAPI = {
  list: (zoneId, params) => api.get(`/zones/${zoneId}/racks`, { params }),
  show: (zoneId, id) => api.get(`/zones/${zoneId}/racks/${id}`),
  create: (zoneId, data) => api.post(`/zones/${zoneId}/racks`, data),
  update: (zoneId, id, data) => api.put(`/zones/${zoneId}/racks/${id}`, data),
  delete: (zoneId, id) => api.delete(`/zones/${zoneId}/racks/${id}`),
  slots: (id) => api.get(`/racks/${id}/slots`),
  updatePosition: (id, data) => api.put(`/racks/${id}/position`, data),
}

// Rack Slots
export const rackSlotAPI = {
  list: (params) => api.get('/rack-slots', { params }),
  show: (id) => api.get(`/rack-slots/${id}`),
  create: (data) => api.post('/rack-slots', data),
  update: (id, data) => api.put(`/rack-slots/${id}`, data),
  delete: (id) => api.delete(`/rack-slots/${id}`),
  assign: (id, data) => api.put(`/rack-slots/${id}/assign`, data),
  unassign: (id) => api.put(`/rack-slots/${id}/unassign`),
  reserve: (id, data) => api.put(`/rack-slots/${id}/reserve`, data),
}

