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
  login: (data) => api.post('/auth/login', data),
  register: (data) => api.post('/auth/register', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
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

// Stock
export const stockAPI = {
  summary: () => api.get('/stock/summary'),
  lowStock: () => api.get('/stock/low-stock'),
  transfer: (data) => api.post('/stock/transfer', data),
  adjust: (data) => api.post('/stock/adjust', data),
}

// Inbounds
export const inboundAPI = {
  list: (params) => api.get('/inbounds', { params }),
  show: (uuid) => api.get(`/inbounds/${uuid}`),
  create: (data) => api.post('/inbounds', data),
  update: (uuid, data) => api.put(`/inbounds/${uuid}`, data),
  pending: () => api.get('/inbounds/pending'),
  receive: (uuid, data) => api.post(`/inbounds/${uuid}/receive`, data),
}

// Outbounds
export const outboundAPI = {
  list: (params) => api.get('/outbounds', { params }),
  show: (uuid) => api.get(`/outbounds/${uuid}`),
  create: (data) => api.post('/outbounds', data),
  update: (uuid, data) => api.put(`/outbounds/${uuid}`, data),
  pending: () => api.get('/outbounds/pending'),
  ship: (uuid, data) => api.post(`/outbounds/${uuid}/ship`, data),
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
  movement: (params) => api.get('/reports/movement', { params }),
  valuation: (params) => api.get('/reports/valuation', { params }),
  warehouseUtilization: (params) => api.get('/reports/warehouse-utilization', { params }),
}

// Settings
export const settingAPI = {
  index: () => api.get('/settings'),
  update: (data) => api.put('/settings', data),
}