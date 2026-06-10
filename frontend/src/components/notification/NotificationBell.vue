<template>
  <div class="relative" @click.outside="showDropdown = false">
    <button @click="toggleDropdown"
      class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors"
      :class="{ 'animate-pulse': unreadCount > 0 }">
      <BellIcon class="w-5 h-5" :class="unreadCount > 0 ? 'text-blue-600' : 'text-gray-500'" />
      <span v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 w-4.5 h-4.5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center min-w-[18px] min-h-[18px] shadow-sm">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-1 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-1 scale-95"
    >
      <div v-if="showDropdown"
        class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-xl border border-gray-100 z-50 max-h-[500px] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="font-bold text-gray-800">Notifikasi</h3>
          <div class="flex gap-2">
            <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-blue-600 font-medium hover:underline">
              Tandai Dibaca
            </button>
            <router-link to="/notifications" @click="showDropdown = false"
              class="text-xs text-gray-500 font-medium hover:underline">
              Lihat Semua
            </router-link>
          </div>
        </div>

        <!-- List -->
        <div class="overflow-y-auto flex-1">
          <div v-if="loading" class="py-8 text-center text-gray-400 text-sm">
            <ArrowPathIcon class="w-5 h-5 animate-spin mx-auto mb-2" />
            Memuat notifikasi...
          </div>
          <div v-else-if="items.length === 0" class="py-8 text-center">
            <BellSlashIcon class="w-8 h-8 text-gray-300 mx-auto mb-2" />
            <p class="text-sm text-gray-400">Tidak ada notifikasi</p>
          </div>
          <div v-else>
            <div v-for="notif in items" :key="notif.id"
              @click="handleClick(notif)"
              class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50 cursor-pointer transition-colors border-b border-gray-50 last:border-b-0"
              :class="{ 'bg-blue-50/30': !notif.read_at }">
              <div class="p-2 rounded-xl shadow-sm flex-shrink-0 mt-0.5"
                :class="getTypeClass(notif.type)">
                <component :is="getTypeIcon(notif.type)" class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ notif.title }}</p>
                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ notif.message }}</p>
                <p class="text-[11px] text-gray-400 mt-1">{{ formatTime(notif.created_at) }}</p>
              </div>
              <div v-if="!notif.read_at" class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationDataStore } from '../stores/notificationData'
import {
  BellIcon,
  BellSlashIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  InformationCircleIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const store = useNotificationDataStore()
const router = useRouter()
const showDropdown = ref(false)

const items = computed(() => store.items.slice(0, 10))
const unreadCount = computed(() => store.unreadCount)
const loading = computed(() => store.loading)

function toggleDropdown() {
  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    store.fetchNotifications()
  }
}

async function handleClick(notif) {
  if (!notif.read_at) {
    await store.markAsRead(notif.id)
  }
  showDropdown.value = false
}

async function markAllRead() {
  await store.markAllAsRead()
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
  return new Date(dateStr).toLocaleDateString('id-ID')
}

let pollInterval = null
onMounted(async () => {
  await store.fetchUnreadCount()
  pollInterval = setInterval(() => store.fetchUnreadCount(), 30000)
})
onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
