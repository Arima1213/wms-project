import axios from 'axios'

const api = axios.create({
  baseURL: (import.meta.env.VITE_API_URL || 'http://localhost:8080/api') + '/v1',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('wms_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('wms_token')
      localStorage.removeItem('wms_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

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
}

// Settings
export const settingAPI = {
  index: () => api.get('/settings'),
  update: (data) => api.put('/settings', data),
}