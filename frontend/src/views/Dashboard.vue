<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="card p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ stat.label }}</p>
            <p class="text-2xl font-bold mt-1">{{ stat.value }}</p>
          </div>
          <div :class="stat.color" class="p-3 rounded-lg">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
        </div>
        <p class="text-sm text-gray-400 mt-2">{{ stat.subtitle }}</p>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card p-6">
        <h3 class="font-semibold mb-4">Stock Summary</h3>
        <div class="space-y-3">
          <div v-for="item in stockSummary" :key="item.name" class="flex items-center gap-3">
            <div class="flex-1">
              <div class="flex justify-between text-sm mb-1">
                <span>{{ item.name }}</span>
                <span class="font-medium">{{ item.value }}%</span>
              </div>
              <div class="h-2 bg-gray-100 rounded-full">
                <div :class="item.color" class="h-2 rounded-full transition-all" :style="{ width: item.value + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card p-6">
        <h3 class="font-semibold mb-4">Warehouse Utilization</h3>
        <div class="space-y-4">
          <div v-for="wh in warehouseUtil" :key="wh.name" class="space-y-1">
            <div class="flex justify-between text-sm">
              <span>{{ wh.name }}</span>
              <span class="text-gray-500">{{ wh.used }}/{{ wh.total }} ({{ wh.percent }}%)</span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full">
              <div :class="wh.color" class="h-2 rounded-full" :style="{ width: wh.percent + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card p-6">
      <h3 class="font-semibold mb-4">Recent Activity</h3>
      <div class="space-y-3">
        <div v-for="activity in recentActivities" :key="activity.id"
          class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
          <div :class="activity.iconColor" class="p-2 rounded-lg">
            <component :is="activity.icon" class="w-5 h-5" />
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">{{ activity.title }}</p>
            <p class="text-xs text-gray-500">{{ activity.desc }}</p>
          </div>
          <span class="text-xs text-gray-400">{{ activity.time }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  CubeIcon,
  ArrowUpCircleIcon,
  ArrowDownCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const stats = ref([
  { label: 'Total Products', value: '1,248', subtitle: '+42 this week', icon: CubeIcon, color: 'bg-blue-100 text-blue-600' },
  { label: 'Inbound Today', value: '24', subtitle: '+12 from yesterday', icon: ArrowUpCircleIcon, color: 'bg-green-100 text-green-600' },
  { label: 'Outbound Today', value: '18', subtitle: '-5 from yesterday', icon: ArrowDownCircleIcon, color: 'bg-orange-100 text-orange-600' },
  { label: 'Low Stock Items', value: '8', subtitle: 'Needs attention', icon: ExclamationTriangleIcon, color: 'bg-red-100 text-red-600' },
])

const stockSummary = ref([
  { name: 'Available', value: 72, color: 'bg-green-500' },
  { name: 'Reserved', value: 18, color: 'bg-blue-500' },
  { name: 'Quarantine', value: 7, color: 'bg-yellow-500' },
  { name: 'Damaged', value: 3, color: 'bg-red-500' },
])

const warehouseUtil = ref([
  { name: 'WH001 - Gudang Utama', used: 7200, total: 10000, percent: 72, color: 'bg-blue-500' },
  { name: 'WH002 - Distribusi Sby', used: 3100, total: 5000, percent: 62, color: 'bg-green-500' },
])

const recentActivities = ref([
  { id: 1, title: 'Inbound #INB-2024-0042 received', desc: 'Kabel USB Type-C x200 dari PT Elektronik Jaya', time: '10 min ago', icon: ArrowUpCircleIcon, iconColor: 'bg-green-100 text-green-600' },
  { id: 2, title: 'Outbound #OUT-2024-0038 shipped', desc: 'Adapter Listrik x50 ke Toko Elektronik Bandung', time: '45 min ago', icon: ArrowDownCircleIcon, iconColor: 'bg-orange-100 text-orange-600' },
  { id: 3, title: 'Stock transfer WH001 -> WH002', desc: 'Plastik PE 0.5mm x50kg dipindahkan', time: '1 hour ago', icon: CubeIcon, iconColor: 'bg-blue-100 text-blue-600' },
  { id: 4, title: 'Low stock alert', desc: 'Kotak Kardon 30x30x30 di bawah min_stock', time: '2 hours ago', icon: ExclamationTriangleIcon, iconColor: 'bg-red-100 text-red-600' },
])
</script>