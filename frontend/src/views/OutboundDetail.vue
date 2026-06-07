<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button @click="$router.push('/outbounds')" class="btn btn-sm btn-ghost p-2 -ml-2">
          <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ outbound?.outbound_number }}</h1>
            <StatusBadge :status="getStatusColor(outbound?.status)" :label="outbound?.status" class="uppercase text-xs" />
          </div>
          <p class="text-sm text-gray-500 mt-0.5">Gudang: {{ outbound?.warehouse?.name }} &middot; Customer: {{ outbound?.customer_name || '-' }}</p>
        </div>
      </div>
      <div class="flex gap-2" v-if="!loading">
        <button v-if="outbound?.status === 'pending'" @click="pickAll" class="btn btn-outline shadow-sm" :disabled="processing">
          Proses Pick
        </button>
        <button v-if="outbound?.status === 'pending' || outbound?.status === 'picking'" @click="shipAll" class="btn btn-primary shadow-sm" :disabled="processing">
          Kirim (Ship)
        </button>
        <button v-if="outbound?.status === 'pending'" @click="cancelOutbound" class="btn btn-outline border-red-200 text-red-600" :disabled="processing">
          Batalkan
        </button>
      </div>
    </div>

    <div v-if="loading" class="py-12 flex justify-center">
      <ArrowPathIcon class="w-8 h-8 animate-spin text-blue-600" />
    </div>

    <div v-else class="space-y-6">
      <!-- Info Card -->
      <div class="card p-6">
        <h3 class="font-bold text-gray-800 mb-4">Informasi Outbound</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Tipe Pengiriman</p>
            <p class="font-medium text-gray-800 capitalize">{{ outbound?.destination_type?.replace('_', ' ') || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Referensi Tujuan</p>
            <p class="font-medium text-gray-800">{{ outbound?.destination_reference || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Tanggal Harapan</p>
            <p class="font-medium text-gray-800">{{ formatDate(outbound?.expected_date) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Tanggal Dikirim</p>
            <p class="font-medium text-gray-800">{{ formatDate(outbound?.shipped_date) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Alamat Pengiriman</p>
            <p class="font-medium text-gray-800">{{ outbound?.shipping_address || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Dibuat Oleh</p>
            <p class="font-medium text-gray-800">{{ outbound?.user?.name || '-' }}</p>
          </div>
        </div>
        <div v-if="outbound?.notes" class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-xs text-gray-500 mb-1">Catatan</p>
          <p class="text-sm text-gray-700">{{ outbound.notes }}</p>
        </div>
      </div>

      <!-- Items Table -->
      <div class="card">
        <div class="p-4 border-b border-gray-100">
          <h3 class="font-bold text-gray-800">Daftar Item ({{ items.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">Produk</th>
                <th class="p-4 text-center">Qty Diharap</th>
                <th class="p-4 text-center">Qty Picked</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="items.length === 0">
                <td colspan="3" class="p-8 text-center text-gray-400">Tidak ada item.</td>
              </tr>
              <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                <td class="p-4">
                  <p class="font-medium text-gray-800">{{ item.product?.name || 'Produk #' + item.product_id }}</p>
                  <p class="text-xs text-gray-500">{{ item.product?.sku || '' }}</p>
                </td>
                <td class="p-4 text-center font-semibold text-gray-800">{{ item.qty }}</td>
                <td class="p-4 text-center">
                  <span :class="item.picked_qty ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                    {{ item.picked_qty ?? '-' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOutboundStore } from '../stores/outbound'
import { useNotificationStore } from '../stores/notification'
import StatusBadge from '../components/common/StatusBadge.vue'
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id as idLocale } from 'date-fns/locale'

const route = useRoute()
const store = useOutboundStore()
const notify = useNotificationStore()

const outbound = ref(null)
const items = ref([])
const loading = ref(false)
const processing = ref(false)

function getStatusColor(status) {
  const map = { 'pending': 'warning', 'picking': 'info', 'shipped': 'success', 'cancelled': 'danger' }
  return map[status?.toLowerCase()] || 'inactive'
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try { return format(new Date(dateStr), 'd MMM yyyy', { locale: idLocale }) } catch { return dateStr }
}

async function fetchData() {
  loading.value = true
  try {
    const data = await store.fetchOne(route.params.id)
    outbound.value = data?.data || data
    items.value = outbound.value.items || []
  } catch (error) {
    // handled by store
  } finally {
    loading.value = false
  }
}

async function pickAll() {
  if (!confirm('Proses pick seluruh item?')) return
  processing.value = true
  try {
    await store.pick(outbound.value.id)
    await fetchData()
  } catch (e) {
    // handled by store
  } finally {
    processing.value = false
  }
}

async function shipAll() {
  if (!confirm('Kirim seluruh item untuk outbound ini?')) return
  processing.value = true
  try {
    await store.ship(outbound.value.id, { notes: 'Shipped via detail page' })
    await fetchData()
  } catch (e) {
    // handled by store
  } finally {
    processing.value = false
  }
}

async function cancelOutbound() {
  if (!confirm('Batalkan outbound ini?')) return
  processing.value = true
  try {
    await store.cancel(outbound.value.id)
    await fetchData()
  } catch (e) {
    // handled by store
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
