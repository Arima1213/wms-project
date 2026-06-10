<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Notifikasi</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Notifikasi'}]" class="mt-1" />
      </div>
      <div class="flex items-center gap-3">
        <button v-if="unreadCount > 0" @click="markAllRead" class="btn btn-sm btn-outline shadow-sm">
          <CheckIcon class="w-4 h-4" />
          Tandai Semua Dibaca
        </button>
        <button @click="fetchData" class="btn btn-sm btn-primary shadow-sm">
          <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          Refresh
        </button>
      </div>
    </div>

    <!-- Unread Summary -->
    <div v-if="unreadCount > 0" class="card p-4 bg-blue-50 border-blue-100">
      <p class="text-sm text-blue-700 flex items-center gap-2">
        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
        Kamu memiliki <strong>{{ unreadCount }}</strong> notifikasi belum dibaca
      </p>
    </div>

    <!-- Notifications List -->
    <div class="space-y-1">
      <!-- Loading -->
      <div v-if="loading && items.length === 0" class="card p-12">
        <ArrowPathIcon class="w-8 h-8 animate-spin text-blue-600 mx-auto mb-3" />
        <p class="text-center text-sm text-gray-400">Memuat notifikasi...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="items.length === 0" class="card p-12 text-center">
        <BellSlashIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 font-medium">Tidak ada notifikasi</p>
        <p class="text-xs text-gray-400 mt-1">Notifikasi akan muncul saat ada alert stok, kadaluarsa, atau overdue</p>
      </div>

      <!-- List -->
      <div v-else>
        <div
          v-for="notif in items"
          :key="notif.id"
          class="card p-5 flex items-start gap-4 transition-colors cursor-pointer hover:bg-gray-50/50"
          :class="{ 'border-l-4 border-l-blue-500 bg-blue-50/10': !notif.read_at }"
          @click="handleClick(notif)"
        >
          <div class="p-3 rounded-xl shadow-sm flex-shrink-0" :class="getTypeClass(notif.type)">
            <component :is="getTypeIcon(notif.type)" class="w-5 h-5" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="font-bold text-gray-800" :class="{ 'text-blue-800': !notif.read_at }">
                  {{ notif.title }}
                </p>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">{{ notif.message }}</p>
              </div>
              <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">{{ formatTime(notif.created_at) }}</span>
            </div>
            <div class="flex items-center gap-3 mt-3">
              <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" :class="getTypeBadge(notif.type)">
                {{ getTypeLabel(notif.type) }}
              </span>
              <span v-if="!notif.read_at" class="text-[11px] text-blue-600 font-medium">Belum dibaca</span>
              <span v-else class="text-[11px] text-gray-400">Sudah dibaca</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button
        :disabled="pagination.current_page <= 1"
        @click="changePage(pagination.current_page - 1)"
        class="btn btn-sm btn-outline"
        :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page <= 1 }"
      >
        Sebelumnya
      </button>
      <span class="text-sm text-gray-500">
        Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
      </span>
      <button
        :disabled="pagination.current_page >= pagination.last_page"
        @click="changePage(pagination.current_page + 1)"
        class="btn btn-sm btn-outline"
        :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page >= pagination.last_page }"
      >
        Selanjutnya
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationDataStore } from '../stores/notificationData'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import {
  BellSlashIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  InformationCircleIcon,
  CheckIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const store = useNotificationDataStore()

const items = computed(() => store.items)
const loading = computed(() => store.loading)
const unreadCount = computed(() => store.unreadCount)
const pagination = computed(() => store.pagination)

async function fetchData() {
  await store.fetchNotifications(pagination.value.current_page)
  await store.fetchUnreadCount()
}

async function handleClick(notif) {
  if (!notif.read_at) {
    await store.markAsRead(notif.id)
  }
}

async function markAllRead() {
  await store.markAllAsRead()
}

function changePage(page) {
  store.fetchNotifications(page)
}

function getTypeClass(type) {
  const map = {
    low_stock: 'bg-rose-100 text-rose-600',
    expiring: 'bg-amber-100 text-amber-600',
    inbound_overdue: 'bg-orange-100 text-orange-600',
    outbound_overdue: 'bg-red-100 text-red-600',
    info: 'bg-blue-100 text-blue-600',
  }
  return map[type] || 'bg-gray-100 text-gray-600'
}

function getTypeIcon(type) {
  const map = {
    low_stock: ExclamationTriangleIcon,
    expiring: ClockIcon,
    inbound_overdue: ArrowDownTrayIcon,
    outbound_overdue: ArrowUpTrayIcon,
  }
  return map[type] || InformationCircleIcon
}

function getTypeLabel(type) {
  const map = {
    low_stock: 'Stok Menipis',
    expiring: 'Akan Kadaluarsa',
    inbound_overdue: 'Inbound Overdue',
    outbound_overdue: 'Outbound Overdue',
    info: 'Informasi',
  }
  return map[type] || type
}

function getTypeBadge(type) {
  const map = {
    low_stock: 'bg-rose-50 text-rose-700',
    expiring: 'bg-amber-50 text-amber-700',
    inbound_overdue: 'bg-orange-50 text-orange-700',
    outbound_overdue: 'bg-red-50 text-red-700',
    info: 'bg-blue-50 text-blue-700',
  }
  return map[type] || 'bg-gray-50 text-gray-700'
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'Baru saja'
  if (mins < 60) return `${mins} menit yang lalu`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} jam yang lalu`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days} hari yang lalu`
  return new Date(dateStr).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  fetchData()
})
</script>
