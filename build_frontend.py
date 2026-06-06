import os, json

BASE = 'C:/Users/ASUS/Downloads/docker-setup/wms-project/frontend'
os.makedirs(BASE, exist_ok=True)

# --- package.json ---
package = {
    "name": "wms-frontend",
    "version": "1.0.0",
    "private": True,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview",
        "lint": "eslint . --ext .vue,.js,.jsx,.cjs,.mjs --fix"
    },
    "dependencies": {
        "vue": "^3.4.0",
        "vue-router": "^4.3.0",
        "pinia": "^2.1.0",
        "@vueuse/core": "^10.9.0",
        "axios": "^1.6.0",
        "konva": "^9.3.0",
        "vue-konva": "^3.0.2",
        "dayjs": "^1.11.0",
        "chart.js": "^4.4.0",
        "vue-chartjs": "^5.3.0",
        "lucide-vue-next": "^0.378.0"
    },
    "devDependencies": {
        "@vitejs/plugin-vue": "^5.0.0",
        "vite": "^5.2.0",
        "autoprefixer": "^10.4.0",
        "postcss": "^8.4.0",
        "tailwindcss": "^3.4.0",
        "eslint": "^8.57.0",
        "eslint-plugin-vue": "^9.26.0"
    }
}

with open(BASE + '/package.json', 'w') as f:
    json.dump(package, f, indent=2)
print("package.json created")

# --- vite.config.js ---
vite_config = """import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://app:8000',
        changeOrigin: true,
      },
    },
  },
})
"""

with open(BASE + '/vite.config.js', 'w') as f:
    f.write(vite_config)
print("vite.config.js created")

# --- tailwind.config.js ---
tailwind_config = """/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' },
        secondary: { 50: '#f5f3ff', 500: '#8b5cf6', 600: '#7c3aed' },
        success: { 500: '#10b981', 600: '#059669' },
        warning: { 500: '#f59e0b', 600: '#d97706' },
        danger: { 500: '#ef4444', 600: '#dc2626' },
      },
      fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
    },
  },
  plugins: [],
}
"""

with open(BASE + '/tailwind.config.js', 'w') as f:
    f.write(tailwind_config)
print("tailwind.config.js created")

# --- postcss.config.js ---
postcss_config = """export default {
  plugins: { tailwindcss: {}, autoprefixer: {} },
}
"""

with open(BASE + '/postcss.config.js', 'w') as f:
    f.write(postcss_config)
print("postcss.config.js created")

# --- index.html ---
index_html = """<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WMS - Warehouse Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
"""

with open(BASE + '/index.html', 'w') as f:
    f.write(index_html)
print("index.html created")

# --- src/main.js ---
src_main = """import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './assets/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
"""

src_dir = BASE + '/src'
os.makedirs(src_dir, exist_ok=True)
with open(src_dir + '/main.js', 'w') as f:
    f.write(src_main)
print("src/main.js created")

# --- src/App.vue ---
app_vue = """<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  authStore.checkAuth()
})
</script>
"""

with open(src_dir + '/App.vue', 'w') as f:
    f.write(app_vue)
print("src/App.vue created")

# --- src/assets/main.css ---
assets_dir = src_dir + '/assets'
os.makedirs(assets_dir, exist_ok=True)

main_css = """@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  body {
    @apply bg-gray-50 text-gray-900 font-sans;
  }
  * { box-sizing: border-box; }
}

@layer components {
  .btn {
    @apply px-4 py-2 rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
  }
  .btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
  }
  .btn-secondary {
    @apply bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-400;
  }
  .btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
  }
  .btn-success {
    @apply bg-green-600 text-white hover:bg-green-700 focus:ring-green-500;
  }
  .card {
    @apply bg-white rounded-xl shadow-sm border border-gray-200;
  }
  .input {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all;
  }
  .label {
    @apply block text-sm font-medium text-gray-700 mb-1;
  }
  .badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
  }
  .badge-success { @apply bg-green-100 text-green-800; }
  .badge-warning { @apply bg-yellow-100 text-yellow-800; }
  .badge-danger  { @apply bg-red-100 text-red-800; }
  .badge-info    { @apply bg-blue-100 text-blue-800; }
  .badge-gray    { @apply bg-gray-100 text-gray-800; }
  .table-wrap { @apply overflow-x-auto; }
  .data-table { @apply w-full text-sm; }
  .data-table th { @apply px-4 py-3 text-left font-semibold text-gray-600 bg-gray-50 border-b; }
  .data-table td { @apply px-4 py-3 border-b border-gray-100; }
  .data-table tr:hover td { @apply bg-blue-50; }
}
"""

with open(assets_dir + '/main.css', 'w') as f:
    f.write(main_css)
print("src/assets/main.css created")

# --- src/router/index.js ---
router_dir = src_dir + '/router'
os.makedirs(router_dir, exist_ok=True)

router_index = """import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/Login.vue'), meta: { guest: true } },
  { path: '/', component: () => import('@/layouts/AppLayout.vue'), children: [
    { path: '', name: 'Dashboard', component: () => import('@/views/Dashboard.vue'), meta: { auth: true } },
    { path: 'warehouses', name: 'Warehouses', component: () => import('@/views/Warehouses.vue'), meta: { auth: true } },
    { path: 'warehouses/:id', name: 'WarehouseDetail', component: () => import('@/views/WarehouseDetail.vue'), meta: { auth: true } },
    { path: 'products', name: 'Products', component: () => import('@/views/Products.vue'), meta: { auth: true } },
    { path: 'products/:id', name: 'ProductDetail', component: () => import('@/views/ProductDetail.vue'), meta: { auth: true } },
    { path: 'categories', name: 'Categories', component: () => import('@/views/Categories.vue'), meta: { auth: true } },
    { path: 'suppliers', name: 'Suppliers', component: () => import('@/views/Suppliers.vue'), meta: { auth: true } },
    { path: 'customers', name: 'Customers', component: () => import('@/views/Customers.vue'), meta: { auth: true } },
    { path: 'inbounds', name: 'Inbounds', component: () => import('@/views/Inbounds.vue'), meta: { auth: true } },
    { path: 'outbounds', name: 'Outbounds', component: () => import('@/views/Outbounds.vue'), meta: { auth: true } },
    { path: 'stocks', name: 'Stocks', component: () => import('@/views/Stocks.vue'), meta: { auth: true } },
    { path: 'planograms', name: 'Planograms', component: () => import('@/views/Planograms.vue'), meta: { auth: true } },
    { path: 'planograms/:id', name: 'PlanogramEditor', component: () => import('@/views/PlanogramEditor.vue'), meta: { auth: true } },
    { path: 'reports', name: 'Reports', component: () => import('@/views/Reports.vue'), meta: { auth: true } },
    { path: 'settings', name: 'Settings', component: () => import('@/views/Settings.vue'), meta: { auth: true } },
  ]},
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && auth.isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router
"""

with open(router_dir + '/index.js', 'w') as f:
    f.write(router_index)
print("src/router/index.js created")

# --- src/stores/auth.js ---
stores_dir = src_dir + '/stores'
os.makedirs(stores_dir, exist_ok=True)

auth_store = """import { defineStore } from 'pinia'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('wms_token') || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role?.name ?? 'viewer',
  },
  actions: {
    async login(email, password) {
      const res = await api.post('/auth/login', { email, password })
      this.token = res.data.token
      this.user = res.data.user
      localStorage.setItem('wms_token', this.token)
      api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      return res.data
    },
    async logout() {
      try { await api.post('/auth/logout') } catch {}
      this.token = null
      this.user = null
      localStorage.removeItem('wms_token')
      delete api.defaults.headers.common['Authorization']
    },
    async checkAuth() {
      if (this.token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        try {
          const res = await api.get('/auth/me')
          this.user = res.data
        } catch {
          this.token = null
          localStorage.removeItem('wms_token')
        }
      }
    },
  },
})
"""

with open(stores_dir + '/auth.js', 'w') as f:
    f.write(auth_store)
print("src/stores/auth.js created")

# --- src/lib/api.js ---
lib_dir = src_dir + '/lib'
os.makedirs(lib_dir, exist_ok=True)

api_lib = """import axios from 'axios'

const instance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 30000,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
})

instance.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('wms_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default instance
"""

with open(lib_dir + '/api.js', 'w') as f:
    f.write(api_lib)
print("src/lib/api.js created")

# --- src/layouts/AppLayout.vue ---
layouts_dir = src_dir + '/layouts'
os.makedirs(layouts_dir, exist_ok=True)

app_layout = """<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col fixed h-full">
      <div class="px-6 py-5 border-b border-slate-700">
        <h1 class="text-xl font-bold flex items-center gap-2">
          <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
          </svg>
          WMS Multi-Gudang
        </h1>
      </div>
      <nav class="flex-1 overflow-y-auto py-4">
        <div v-for="group in menuGroups" :key="group.label" class="mb-4">
          <div class="px-6 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ group.label }}</div>
          <router-link
            v-for="item in group.items" :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 px-6 py-2.5 text-sm transition-colors"
            :class="$route.path === item.to ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700'"
          >
            <component :is="item.icon" class="w-5 h-5" />
            {{ item.label }}
          </router-link>
        </div>
      </nav>
      <div class="border-t border-slate-700 p-4">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
            {{ authStore.user?.name?.charAt(0) ?? 'A' }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium truncate">{{ authStore.user?.name }}</div>
            <div class="text-xs text-slate-400">{{ authStore.userRole }}</div>
          </div>
        </div>
        <button @click="handleLogout" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-300 hover:bg-slate-700 rounded-lg transition-colors">
          <LogOut class="w-4 h-4" /> Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64">
      <header class="bg-white border-b sticky top-0 z-10">
        <div class="flex items-center justify-between px-6 py-4">
          <h2 class="text-xl font-semibold text-gray-800">{{ pageTitle }}</h2>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">{{ today }}</span>
          </div>
        </div>
      </header>
      <div class="p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  LayoutDashboard, Building2, Package, Tags, Truck, Users,
  ArrowDownToLine, ArrowUpFromLine, Layers, Map,
  BarChart3, Settings, LogOut, Warehouse
} from 'lucide-vue-next'
import dayjs from 'dayjs'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const today = computed(() => dayjs().format('dddd, D MMMM YYYY'))

const pageTitle = computed(() => {
  const map = {
    '/': 'Dashboard', '/warehouses': 'Gudang', '/products': 'Produk',
    '/categories': 'Kategori', '/suppliers': 'Supplier', '/customers': 'Pelanggan',
    '/inbounds': 'Barang Masuk', '/outbounds': 'Barang Keluar', '/stocks': 'Stok',
    '/planograms': 'Planogram', '/reports': 'Laporan', '/settings': 'Pengaturan',
  }
  return map[route.path] || 'WMS'
})

const menuGroups = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', to: '/', icon: LayoutDashboard },
      { label: 'Gudang', to: '/warehouses', icon: Building2 },
    ],
  },
  {
    label: 'Inventori',
    items: [
      { label: 'Produk', to: '/products', icon: Package },
      { label: 'Kategori', to: '/categories', icon: Tags },
      { label: 'Stok', to: '/stocks', icon: Layers },
    ],
  },
  {
    label: 'Operasi',
    items: [
      { label: 'Barang Masuk', to: '/inbounds', icon: ArrowDownToLine },
      { label: 'Barang Keluar', to: '/outbounds', icon: ArrowUpFromLine },
    ],
  },
  {
    label: 'Master',
    items: [
      { label: 'Supplier', to: '/suppliers', icon: Truck },
      { label: 'Pelanggan', to: '/customers', icon: Users },
    ],
  },
  {
    label: 'Lainnya',
    items: [
      { label: 'Planogram', to: '/planograms', icon: Map },
      { label: 'Laporan', to: '/reports', icon: BarChart3 },
      { label: 'Pengaturan', to: '/settings', icon: Settings },
    ],
  },
]

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
"""

with open(layouts_dir + '/AppLayout.vue', 'w') as f:
    f.write(app_layout)
print("src/layouts/AppLayout.vue created")

# --- src/views/Login.vue ---
views_dir = src_dir + '/views'
os.makedirs(views_dir, exist_ok=True)

login_view = """<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">WMS Multi-Gudang</h1>
        <p class="text-gray-500 mt-1">Sistem Manajemen Gudang</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" type="email" class="input" placeholder="admin@wms.local" required />
        </div>
        <div>
          <label class="label">Password</label>
          <input v-model="form.password" type="password" class="input" placeholder="••••••••" required />
        </div>
        <div v-if="error" class="bg-red-50 text-red-700 text-sm px-4 py-3 rounded-lg">
          {{ error }}
        </div>
        <button type="submit" class="btn btn-primary w-full" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Masuk' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-400 mt-6">
        Demo: admin@wms.local / password
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({ email: 'admin@wms.local', password: 'password' })
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  try {
    await authStore.login(form.value.email, form.value.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login gagal. Cek email & password.'
  } finally {
    loading.value = false
  }
}
</script>
"""

with open(views_dir + '/Login.vue', 'w') as f:
    f.write(login_view)
print("src/views/Login.vue created")

# --- src/views/Dashboard.vue ---
dashboard_view = """<template>
  <div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
      <div class="card p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Total Gudang</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_warehouses }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <Building2 class="w-6 h-6 text-blue-600" />
          </div>
        </div>
      </div>
      <div class="card p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Total Produk</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_products }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <Package class="w-6 h-6 text-green-600" />
          </div>
        </div>
      </div>
      <div class="card p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Barang Masuk (Hari ini)</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.inbound_today }}</p>
          </div>
          <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
            <ArrowDownToLine class="w-6 h-6 text-emerald-600" />
          </div>
        </div>
      </div>
      <div class="card p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Barang Keluar (Hari ini)</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.outbound_today }}</p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <ArrowUpFromLine class="w-6 h-6 text-orange-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- Low Stock Alert -->
    <div v-if="stats.low_stock_alerts > 0" class="card border-l-4 border-l-yellow-500 p-5 mb-6">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
          <AlertTriangle class="w-5 h-5 text-yellow-700" />
        </div>
        <div>
          <p class="font-semibold text-gray-900">Peringatan Stok Rendah</p>
          <p class="text-sm text-gray-500">{{ stats.low_stock_alerts }} produk di bawah stok minimum</p>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-gray-900">Barang Masuk Terbaru</h3>
        </div>
        <div class="divide-y">
          <div v-for="item in stats.recent_inbound" :key="item.id" class="px-5 py-3 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium">{{ item.reference_number }}</p>
              <p class="text-xs text-gray-500">{{ item.supplier?.name ?? 'Tanpa Supplier' }}</p>
            </div>
            <span class="badge badge-success">{{ item.status }}</span>
          </div>
          <div v-if="!stats.recent_inbound?.length" class="px-5 py-4 text-sm text-gray-400 text-center">Belum ada data</div>
        </div>
      </div>
      <div class="card">
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-gray-900">Barang Keluar Terbaru</h3>
        </div>
        <div class="divide-y">
          <div v-for="item in stats.recent_outbound" :key="item.id" class="px-5 py-3 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium">{{ item.reference_number }}</p>
              <p class="text-xs text-gray-500">{{ item.customer?.name ?? 'Tanpa Pelanggan' }}</p>
            </div>
            <span class="badge badge-info">{{ item.status }}</span>
          </div>
          <div v-if="!stats.recent_outbound?.length" class="px-5 py-4 text-sm text-gray-400 text-center">Belum ada data</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Building2, Package, ArrowDownToLine, ArrowUpFromLine, AlertTriangle } from 'lucide-vue-next'

const stats = ref({ total_warehouses: 0, total_products: 0, inbound_today: 0, outbound_today: 0, low_stock_alerts: 0, recent_inbound: [], recent_outbound: [] })

onMounted(async () => {
  try {
    const res = await api.get('/dashboard/stats')
    stats.value = res.data
  } catch (e) {
    console.error('Dashboard error:', e)
  }
})
</script>
"""

with open(views_dir + '/Dashboard.vue', 'w') as f:
    f.write(dashboard_view)
print("src/views/Dashboard.vue created")

# --- Generic CRUD views ---
generic_views = {
    'Warehouses.vue': """<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold">Daftar Gudang</h3>
      <button @click="showForm = true" class="btn btn-primary">+ Tambah Gudang</button>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Kode</th><th>Nama</th><th>Kota</th><th>Total Rack</th><th>Kapasitas</th><th>Status</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items.data" :key="item.id">
              <td class="font-mono text-sm">{{ item.code }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.city }}</td>
              <td>{{ item.total_racks }}</td>
              <td>{{ formatNumber(item.used_capacity) }} / {{ formatNumber(item.total_capacity) }}</td>
              <td><span :class="item.is_active ? 'badge-success' : 'badge-gray'" class="badge">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td class="flex gap-2">
                <router-link :to="'/warehouses/' + item.id" class="text-blue-600 hover:underline text-sm">Detail</router-link>
                <button @click="editItem(item)" class="text-gray-500 hover:text-gray-700 text-sm">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-5 py-3 border-t flex justify-between items-center">
        <span class="text-sm text-gray-500">Total: {{ items.total }}</span>
        <div class="flex gap-1">
          <button v-for="p in items.links" :key="p.label" @click="fetchPage(p.url)" :disabled="!p.url" class="px-3 py-1 text-sm rounded border" :class="p.active ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'">{{ p.label }}</button>
        </div>
      </div>
    </div>

    <!-- Form Modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showForm = false">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4">{{ form.id ? 'Edit Gudang' : 'Tambah Gudang' }}</h3>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Kode</label><input v-model="form.code" class="input" required /></div>
            <div><label class="label">Nama</label><input v-model="form.name" class="input" required /></div>
          </div>
          <div><label class="label">Alamat</label><textarea v-model="form.address" class="input" rows="2"></textarea></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Kota</label><input v-model="form.city" class="input" /></div>
            <div><label class="label">Provinsi</label><input v-model="form.province" class="input" /></div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Total Rack</label><input v-model.number="form.total_racks" type="number" class="input" /></div>
            <div><label class="label">Kapasitas</label><input v-model.number="form.total_capacity" type="number" class="input" /></div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Nama Manager</label><input v-model="form.manager_name" class="input" /></div>
            <div><label class="label">Telepon Manager</label><input v-model="form.manager_phone" class="input" /></div>
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showForm = false" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Building2 } from 'lucide-vue-next'

const items = ref({ data: [], total: 0, links: [] })
const showForm = ref(false)
const form = ref({ code: '', name: '', address: '', city: '', province: '', total_racks: 0, total_capacity: 0, manager_name: '', manager_phone: '' })

const fetchPage = async (url) => {
  const res = await api.get(url)
  items.value = res.data
}

const editItem = (item) => {
  form.value = { ...item }
  showForm.value = true
}

const saveItem = async () => {
  try {
    if (form.value.id) {
      await api.put(`/warehouses/${form.value.id}`, form.value)
    } else {
      await api.post('/warehouses', form.value)
    }
    showForm.value = false
    form.value = { code: '', name: '', address: '', city: '', province: '', total_racks: 0, total_capacity: 0, manager_name: '', manager_phone: '' }
    await fetchPage('/warehouses')
  } catch (e) { alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message)) }
}

const formatNumber = (v) => Number(v || 0).toLocaleString('id-ID')

onMounted(() => fetchPage('/warehouses'))
</script>
""",

    'Products.vue': """<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold">Daftar Produk</h3>
      <button @click="showForm = true" class="btn btn-primary">+ Tambah Produk</button>
    </div>
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Unit</th><th>Stok Min</th><th>Stok Max</th><th>Aksi</th></tr></thead>
          <tbody>
            <tr v-for="item in items.data" :key="item.id">
              <td class="font-mono text-sm">{{ item.code }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.category?.name ?? '-' }}</td>
              <td>{{ item.unit }}</td>
              <td>{{ item.min_stock }}</td>
              <td>{{ item.max_stock }}</td>
              <td><router-link :to="'/products/' + item.id" class="text-blue-600 hover:underline text-sm">Detail</router-link></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showForm = false">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4">{{ form.id ? 'Edit Produk' : 'Tambah Produk' }}</h3>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Kode</label><input v-model="form.code" class="input" required /></div>
            <div><label class="label">Nama</label><input v-model="form.name" class="input" required /></div>
          </div>
          <div><label class="label">Deskripsi</label><textarea v-model="form.description" class="input" rows="2"></textarea></div>
          <div class="grid grid-cols-3 gap-4">
            <div><label class="label">Unit</label><input v-model="form.unit" class="input" /></div>
            <div><label class="label">Min Stok</label><input v-model.number="form.min_stock" type="number" class="input" /></div>
            <div><label class="label">Max Stok</label><input v-model.number="form.max_stock" type="number" class="input" /></div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Barcode</label><input v-model="form.barcode" class="input" /></div>
            <div><label class="label">SKU</label><input v-model="form.sku" class="input" /></div>
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showForm = false" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

const items = ref({ data: [], total: 0 })
const showForm = ref(false)
const form = ref({ code: '', name: '', description: '', unit: 'pcs', min_stock: 0, max_stock: 0, barcode: '', sku: '' })

onMounted(async () => {
  const res = await api.get('/products')
  items.value = res.data
})

const saveItem = async () => {
  try {
    if (form.value.id) await api.put(`/products/${form.value.id}`, form.value)
    else await api.post('/products', form.value)
    showForm.value = false
    const res = await api.get('/products')
    items.value = res.data
  } catch (e) { alert('Gagal menyimpan') }
}
</script>
""",

    'Categories.vue': """<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold">Kategori Produk</h3>
      <button @click="form = { name: '' }; showForm = true" class="btn btn-primary">+ Tambah Kategori</button>
    </div>
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Kode</th><th>Parent</th><th>Aksi</th></tr></thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.name }}</td>
              <td class="font-mono text-sm">{{ item.code }}</td>
              <td>{{ item.parent?.name ?? '-' }}</td>
              <td><button @click="editItem(item)" class="text-blue-600 hover:underline text-sm">Edit</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showForm = false">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Kategori</h3>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div><label class="label">Nama</label><input v-model="form.name" class="input" required /></div>
          <div><label class="label">Kode</label><input v-model="form.code" class="input" /></div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="showForm = false" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'

const items = ref([])
const showForm = ref(false)
const form = ref({ id: null, name: '', code: '' })

const fetchItems = async () => {
  const res = await api.get('/categories')
  items.value = res.data
}

const editItem = (item) => { form.value = { ...item }; showForm.value = true }

const saveItem = async () => {
  try {
    if (form.value.id) await api.put(`/categories/${form.value.id}`, form.value)
    else await api.post('/categories', form.value)
    showForm.value = false
    await fetchItems()
  } catch { alert('Gagal menyimpan') }
}

onMounted(fetchItems)
</script>
""",
}

for fname, content in generic_views.items():
    with open(views_dir + '/' + fname, 'w') as f:
        f.write(content)
    print(f"View: {fname}")

# --- Create placeholder views for remaining routes ---
placeholder_views = [
    'WarehouseDetail.vue', 'ProductDetail.vue', 'Suppliers.vue', 'Customers.vue',
    'Inbounds.vue', 'Outbounds.vue', 'Stocks.vue', 'Planograms.vue',
    'PlanogramEditor.vue', 'Reports.vue', 'Settings.vue'
]

for fname in placeholder_views:
    content = f"""<template>
  <div class="card p-8 text-center">
    <h3 class="text-lg font-semibold text-gray-700 mb-2">{fname.replace('.vue','').replace(/([A-Z])/g,' $1').trim()}</h3>
    <p class="text-gray-400 text-sm">Halaman dalam pengembangan</p>
  </div>
</template>
"""
    with open(views_dir + '/' + fname, 'w') as f:
        f.write(content)
    print(f"Placeholder: {fname}")

# --- Dockerfile for frontend ---
dockerfile_fe = """FROM node:20-alpine AS builder
WORKDIR /app
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY docker/nginx-frontend.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
"""

with open(BASE + '/Dockerfile', 'w') as f:
    f.write(dockerfile_fe)
print("frontend/Dockerfile created")

# --- nginx frontend config ---
os.makedirs(BASE + '/docker', exist_ok=True)
nginx_fe = """server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://app:8000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
"""
with open(BASE + '/docker/nginx-frontend.conf', 'w') as f:
    f.write(nginx_fe)
print("docker/nginx-frontend.conf created")

# --- .env.example for frontend ---
env_example_fe = """VITE_API_URL=/api
VITE_APP_NAME=WMS Multi-Gudang
"""

with open(BASE + '/.env.example', 'w') as f:
    f.write(env_example_fe)
print("frontend/.env.example created")

# --- README ---
readme = f"""# WMS Multi-Gudang

Sistem Manajemen Gudang dengan support multi-gudang, planogram interaktif, dan tracking barang masuk/keluar.

## Tech Stack
- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Vue 3 + Vite + TailwindCSS + Konva.js
- **Database**: PostgreSQL + PostGIS
- **Cache/Queue**: Redis
- **Object Storage**: MinIO
- **Search**: Meilisearch
- **Container**: Docker + Docker Compose

## Quick Start

```bash
# Clone project
cd wms-project

# Start semua service
make up

# Atau manual:
docker compose up -d

# Tunggu ~2 menit, lalu:
make migrate
make seed
```

Akses:
- API: http://localhost:8000
- Frontend: http://localhost:3000
- MinIO Console: http://localhost:9001 (minioadmin / minioadmin)
- Meilisearch: http://localhost:7700

## Default Login
- Email: admin@wms.local
- Password: password

## Struktur Folder
```
wms-project/
├── backend/     (Laravel 11 API)
├── frontend/    (Vue 3 SPA)
├── docker/      (Nginx configs, SSL)
├── docker-compose.yml
└── Makefile
```

## Perintah Penting
```bash
make logs-app    # Lihat logs backend
make shell-app   # Bash ke container app
make shell-db    # PostgreSQL shell
make migrate     # Jalankan migration
make fresh       # Fresh migrate + seed
make restart     # Restart container
```
"""

with open(BASE + '/README.md', 'w') as f:
    f.write(readme)
print("README.md created")

print("\\n=== Frontend scaffold complete! ===")