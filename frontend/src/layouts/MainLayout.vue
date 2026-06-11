<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Mobile overlay -->
    <transition name="fade">
      <div
        v-if="sidebarOpen && !isDesktop"
        class="fixed inset-0 z-20 bg-black/50"
        @click="sidebarOpen = false"
      />
    </transition>

    <!-- Sidebar -->
    <transition name="slide">
      <aside
        v-if="sidebarOpen || isDesktop"
        :class="sidebarClasses"
      >
        <!-- Logo + Toggle -->
        <div class="p-4 border-b border-slate-700 flex items-center justify-between h-16 shrink-0">
          <div v-if="!sidebarCollapsed || !isDesktop" class="min-w-0 flex-1">
            <h1 class="text-lg font-bold truncate">WMS</h1>
            <p class="text-xs text-slate-400 truncate">Multi-Gudang System</p>
          </div>
          <button
            v-if="isDesktop"
            @click="sidebarCollapsed = !sidebarCollapsed; sidebarOpen = false"
            class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-700 shrink-0"
            :title="sidebarCollapsed ? 'Perluas' : 'Ciutkan'"
          >
            <ChevronDoubleLeftIcon v-if="!sidebarCollapsed" class="w-4 h-4" />
            <ChevronDoubleRightIcon v-else class="w-4 h-4" />
          </button>
          <button
            v-if="!isDesktop"
            @click="sidebarOpen = false"
            class="text-slate-400 hover:text-white p-1 shrink-0"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Nav items -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-0.5 scrollbar-thin">
          <router-link
            v-for="item in menuItems" :key="item.to" :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors group"
            :class="isActive(item.to) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700'"
            @click="onNavClick"
            :title="sidebarCollapsed && isDesktop ? item.label : ''"
          >
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            <span v-show="!sidebarCollapsed || !isDesktop" class="truncate text-sm">{{ item.label }}</span>
          </router-link>
        </nav>

        <!-- User section -->
        <div class="p-4 border-t border-slate-700 shrink-0">
          <div class="flex items-center gap-3 mb-3" :class="sidebarCollapsed && isDesktop ? 'justify-center' : ''">
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold shrink-0">
              {{ userInitials }}
            </div>
            <div v-if="!sidebarCollapsed || !isDesktop" class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ user?.name || 'User' }}</p>
              <p class="text-xs text-slate-400 truncate">{{ user?.email }}</p>
            </div>
          </div>
          <button
            @click="logout"
            class="w-full flex items-center gap-2 px-3 py-2 text-slate-300 hover:bg-slate-700 rounded-lg transition-colors"
            :class="sidebarCollapsed && isDesktop ? 'justify-center' : ''"
            :title="sidebarCollapsed && isDesktop ? 'Logout' : ''"
          >
            <ArrowRightOnRectangleIcon class="w-5 h-5 shrink-0" />
            <span v-if="!sidebarCollapsed || !isDesktop" class="text-sm">Logout</span>
          </button>
        </div>
      </aside>
    </transition>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <header class="bg-white border-b px-4 md:px-6 py-3 md:py-4 flex items-center gap-3 shrink-0">
        <!-- Hamburger button -->
        <button
          @click="sidebarOpen = true"
          class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
          :class="isDesktop ? 'hidden' : ''"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <!-- On desktop when collapsed, show hamburger to expand -->
        <button
          v-if="isDesktop && sidebarCollapsed"
          @click="sidebarCollapsed = false; sidebarOpen = true"
          class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <h2 class="text-base md:text-lg font-semibold text-gray-800 truncate">{{ pageTitle }}</h2>

        <div class="flex items-center gap-3 md:gap-4 ml-auto shrink-0">
          <NotificationBell />
          <span class="text-xs md:text-sm text-gray-500 hidden sm:block">{{ currentDate }}</span>
        </div>
      </header>

      <div class="flex-1 overflow-auto p-3 md:p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import NotificationBell from '../components/notification/NotificationBell.vue'
import {
  HomeIcon,
  BuildingOfficeIcon,
  TagIcon,
  CubeIcon,
  ArrowUpCircleIcon,
  ArrowDownCircleIcon,
  ClipboardDocumentListIcon,
  MapIcon,
  BellIcon,
  ChartBarIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  MapPinIcon,
  ServerStackIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
  ArrowUturnLeftIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()

const authStore = useAuthStore()

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const isDesktop = ref(window.innerWidth >= 1024)

const checkScreen = () => {
  isDesktop.value = window.innerWidth >= 1024
  if (isDesktop.value && !sidebarCollapsed.value) sidebarOpen.value = true
  if (!isDesktop.value) sidebarCollapsed.value = false
}

onMounted(() => {
  checkScreen()
  window.addEventListener('resize', checkScreen)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkScreen)
})

const sidebarClasses = computed(() => {
  if (isDesktop.value && sidebarCollapsed.value) {
    return 'bg-slate-800 text-white flex flex-col shrink-0 w-16 relative z-10'
  }
  if (isDesktop.value) {
    return 'bg-slate-800 text-white flex flex-col shrink-0 w-64 relative z-10'
  }
  return 'bg-slate-800 text-white flex flex-col shrink-0 w-72 fixed inset-y-0 left-0 z-30 shadow-2xl'
})

const user = computed(() => authStore.user || {})
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
  { to: '/returns', label: 'Retur', icon: ArrowUturnLeftIcon },
  { to: '/reports', label: 'Laporan', icon: ChartBarIcon },
  { to: '/notifications', label: 'Notifikasi', icon: BellIcon },
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

const onNavClick = () => {
  if (!isDesktop.value) sidebarOpen.value = false
}

const logout = () => {
  authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
/* Thin scrollbar for sidebar nav */
.scrollbar-thin::-webkit-scrollbar {
  width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.15);
  border-radius: 4px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
</style>
