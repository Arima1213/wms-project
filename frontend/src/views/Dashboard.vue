<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard'}]" class="mt-1" />
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500 bg-white px-3 py-1.5 rounded-lg border border-gray-100 shadow-sm">
          Terakhir diperbarui: {{ currentTime }}
        </span>
        <button @click="fetchData" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
          <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': store.loading }" />
          Refresh
        </button>
      </div>
    </div>

    <div v-if="store.loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
      <div v-for="i in 4" :key="i" class="card h-28 bg-white border border-gray-100"></div>
    </div>

    <!-- Stats Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="card p-5 relative overflow-hidden group">
        <!-- Background subtle glow -->
        <div :class="stat.glowColor" class="absolute -right-4 -top-4 w-24 h-24 rounded-full blur-2xl opacity-20 group-hover:opacity-40 transition-opacity duration-500"></div>
        
        <div class="flex items-center justify-between relative z-10">
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ stat.label }}</p>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ stat.value }}</p>
          </div>
          <div :class="stat.color" class="p-3 rounded-xl shadow-sm">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
        </div>
        <p class="text-xs font-medium mt-3 flex items-center gap-1 relative z-10" :class="stat.trendUp ? 'text-emerald-600' : 'text-orange-600'">
          <ArrowTrendingUpIcon v-if="stat.trendUp" class="w-3 h-3" />
          <ArrowTrendingDownIcon v-else class="w-3 h-3" />
          {{ stat.subtitle }}
        </p>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card p-6 border-t-4 border-t-blue-500 relative">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-bold text-gray-800">Ringkasan Status Stok</h3>
          <button class="text-xs text-blue-600 font-medium hover:underline">Lihat Rincian</button>
        </div>
        <div class="space-y-5">
          <div v-for="item in stockSummary" :key="item.name" class="relative">
            <div class="flex justify-between text-sm mb-1.5">
              <span class="font-medium text-gray-700">{{ item.name }}</span>
              <span class="font-bold text-gray-800">{{ item.value }}%</span>
            </div>
            <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden shadow-inner">
              <div :class="item.color" class="h-full rounded-full transition-all duration-1000 ease-out" :style="{ width: item.value + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card p-6 border-t-4 border-t-indigo-500">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-bold text-gray-800">Utilisasi Ruang Gudang</h3>
          <router-link to="/warehouses" class="text-xs text-indigo-600 font-medium hover:underline">Kelola Gudang</router-link>
        </div>
        <div class="space-y-5">
          <div v-if="warehouseUtil.length === 0" class="text-center py-6 text-gray-400 text-sm">
            Belum ada data gudang.
          </div>
          <div v-for="wh in warehouseUtil" :key="wh.name" class="space-y-1.5">
            <div class="flex justify-between items-end">
              <div>
                <p class="text-sm font-bold text-gray-800">{{ wh.name }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(wh.used) }} / {{ formatNumber(wh.total) }} m²</p>
              </div>
              <span class="text-sm font-bold" :class="wh.percent > 80 ? 'text-red-600' : 'text-indigo-600'">{{ wh.percent }}%</span>
            </div>
            <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden shadow-inner">
              <div :class="wh.percent > 80 ? 'bg-red-500' : 'bg-indigo-500'" class="h-full rounded-full transition-all duration-1000 ease-out" :style="{ width: wh.percent + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card p-6 border border-gray-100 shadow-sm">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-bold text-gray-800 flex items-center gap-2">
          <ClockIcon class="w-5 h-5 text-gray-500" />
          Aktivitas Terakhir
        </h3>
      </div>
      <div class="space-y-4">
        <div v-if="recentActivities.length === 0" class="text-center py-8 text-gray-400 text-sm">
          Belum ada aktivitas.
        </div>
        <div v-for="activity in recentActivities" :key="activity.id"
          class="flex items-start gap-4 p-4 bg-gray-50/50 hover:bg-gray-50 rounded-xl border border-transparent hover:border-gray-100 transition-colors group">
          <div :class="activity.iconColor" class="p-2.5 rounded-xl shadow-sm mt-0.5">
            <component :is="activity.icon" class="w-5 h-5" />
          </div>
          <div class="flex-1">
            <p class="text-sm font-bold text-gray-800 group-hover:text-blue-600 transition-colors">{{ activity.title }}</p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ activity.desc }}</p>
          </div>
          <span class="text-xs font-medium text-gray-400 whitespace-nowrap">{{ activity.time }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '../stores/dashboard'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import {
  CubeIcon,
  ArrowUpCircleIcon,
  ArrowDownCircleIcon,
  ExclamationTriangleIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  ClockIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const store = useDashboardStore()
const currentTime = ref(new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }))

// Fallback logic if API returns nothing
const stats = computed(() => {
  const m = store.metrics || {}
  return [
    { label: 'Total Produk Aktif', value: m.total_products || '1,248', subtitle: 'SKU terdaftar', icon: CubeIcon, color: 'bg-blue-100 text-blue-600', glowColor: 'bg-blue-500', trendUp: true },
    { label: 'Inbound Hari Ini', value: m.inbound_today || '24', subtitle: 'transaksi penerimaan', icon: ArrowUpCircleIcon, color: 'bg-emerald-100 text-emerald-600', glowColor: 'bg-emerald-500', trendUp: true },
    { label: 'Outbound Hari Ini', value: m.outbound_today || '18', subtitle: 'transaksi pengiriman', icon: ArrowDownCircleIcon, color: 'bg-orange-100 text-orange-600', glowColor: 'bg-orange-500', trendUp: false },
    { label: 'Peringatan Stok', value: m.low_stock || '8', subtitle: 'butuh restock segera', icon: ExclamationTriangleIcon, color: 'bg-rose-100 text-rose-600', glowColor: 'bg-rose-500', trendUp: false },
  ]
})

const stockSummary = computed(() => {
  // Mock data or mapped from API
  return [
    { name: 'Tersedia (Available)', value: 72, color: 'bg-emerald-500' },
    { name: 'Dipesan (Reserved)', value: 18, color: 'bg-blue-500' },
    { name: 'Karantina (Quarantine)', value: 7, color: 'bg-amber-500' },
    { name: 'Rusak (Damaged)', value: 3, color: 'bg-rose-500' },
  ]
})

const warehouseUtil = computed(() => {
  if (store.warehouseUtilization && store.warehouseUtilization.length) {
    return store.warehouseUtilization.map(wh => ({
      name: wh.warehouse_name,
      used: wh.used_capacity,
      total: wh.total_capacity,
      percent: Math.round((wh.used_capacity / wh.total_capacity) * 100) || 0
    }))
  }
  return [
    { name: 'WH001 - Gudang Utama', used: 7200, total: 10000, percent: 72 },
    { name: 'WH002 - Gudang Pendingin SBY', used: 4100, total: 5000, percent: 82 },
  ]
})

const recentActivities = computed(() => {
  if (store.recentActivity && store.recentActivity.length) {
    return store.recentActivity.map((a, i) => ({
      id: i,
      title: a.title,
      desc: a.description,
      time: a.time_ago,
      icon: a.type === 'inbound' ? ArrowUpCircleIcon : a.type === 'outbound' ? ArrowDownCircleIcon : CubeIcon,
      iconColor: a.type === 'inbound' ? 'bg-emerald-100 text-emerald-600' : a.type === 'outbound' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600'
    }))
  }
  return [
    { id: 1, title: 'Penerimaan Inbound #INB-2024-0042', desc: 'Kabel USB Type-C x200 dari PT Elektronik Jaya diterima di Gudang Utama.', time: '10 mnt yang lalu', icon: ArrowUpCircleIcon, iconColor: 'bg-emerald-100 text-emerald-600' },
    { id: 2, title: 'Pengiriman Outbound #OUT-2024-0038', desc: 'Adapter Listrik x50 diberangkatkan ke Toko Elektronik Bandung.', time: '45 mnt yang lalu', icon: ArrowDownCircleIcon, iconColor: 'bg-orange-100 text-orange-600' },
    { id: 3, title: 'Transfer Stok Internal WH001 -> WH002', desc: 'Plastik PE 0.5mm x50kg dipindahkan dan disetujui.', time: '1 jam yang lalu', icon: CubeIcon, iconColor: 'bg-blue-100 text-blue-600' },
    { id: 4, title: 'Peringatan Stok Menipis', desc: 'Kotak Kardon 30x30x30 menyentuh batas Reorder Point (Sisa: 10).', time: '2 jam yang lalu', icon: ExclamationTriangleIcon, iconColor: 'bg-rose-100 text-rose-600' },
  ]
})

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num)
}

async function fetchData() {
  await store.fetchDashboard()
  await store.fetchReports()
  currentTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  fetchData()
})
</script>