<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col">
      <div class="p-6 border-b border-slate-700">
        <h1 class="text-xl font-bold">WMS</h1>
        <p class="text-sm text-slate-400">Multi-Gudang System</p>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <router-link v-for="item in menuItems" :key="item.to" :to="item.to"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors"
          :class="isActive(item.to) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700'">
          <component :is="item.icon" class="w-5 h-5" />
          {{ item.label }}
        </router-link>
      </nav>

      <div class="p-4 border-t border-slate-700">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
            {{ userInitials }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ user?.name || 'User' }}</p>
            <p class="text-xs text-slate-400 truncate">{{ user?.email }}</p>
          </div>
        </div>
        <button @click="logout" class="w-full flex items-center gap-2 px-4 py-2 text-slate-300 hover:bg-slate-700 rounded-lg transition-colors">
          <DocumentTextIcon,
  MapPinIcon,
  ServerStackIcon,
  ArrowRightOnRectangleIcon class="w-5 h-5" />
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white border-b px-6 py-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h2>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-500">{{ currentDate }}</span>
        </div>
      </header>

      <div class="flex-1 overflow-auto p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  HomeIcon,
  BuildingOfficeIcon,
  TagIcon,
  CubeIcon,
  ArrowUpCircleIcon,
  ArrowDownCircleIcon,
  ClipboardDocumentListIcon,
  MapIcon,
  ChartBarIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  MapPinIcon,
  ServerStackIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()

const user = computed(() => JSON.parse(localStorage.getItem('wms_user') || '{}'))
const userInitials = computed(() => {
  const name = user.value.name || 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const menuItems = [
  { to: '/', label: 'Dashboard', icon: HomeIcon },
  { to: '/warehouses', label: 'Gudang', icon: BuildingOfficeIcon },
  { to: '/categories', label: 'Kategori', icon: TagIcon },
  { to: '/products', label: 'Produk', icon: CubeIcon },
  { to: '/inbounds', label: 'Barang Masuk', icon: ArrowUpCircleIcon },
  { to: '/outbounds', label: 'Barang Keluar', icon: ArrowDownCircleIcon },
  { to: '/stock', label: 'Stok', icon: ClipboardDocumentListIcon },
  { to: '/stock-opnames', label: 'Stock Opname', icon: ClipboardDocumentListIcon },
  { to: '/transfers', label: 'Transfer Stok', icon: ArrowUpCircleIcon },
  { to: '/planograms', label: 'Planogram', icon: MapIcon },
  { to: '/reports', label: 'Laporan', icon: ChartBarIcon },
  { to: '/zones', label: 'Zona', icon: MapPinIcon },
  { to: '/racks', label: 'Rak & Slot', icon: ServerStackIcon },
  { to: '/documents', label: 'Dokumen', icon: DocumentTextIcon },
  { to: '/audit-logs', label: 'Audit Log', icon: ClipboardDocumentListIcon },
  { to: '/settings', label: 'Pengaturan', icon: Cog6ToothIcon },
]

const pageTitle = computed(() => route.name || 'Dashboard')
const currentDate = computed(() => new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

const logout = () => {
  localStorage.removeItem('wms_token')
  localStorage.removeItem('wms_user')
  router.push('/login')
}
</script>