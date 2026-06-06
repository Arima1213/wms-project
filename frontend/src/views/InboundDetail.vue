<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button @click="$router.push('/inbounds')" class="btn btn-sm btn-ghost p-2 -ml-2">
          <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ inbound?.inbound_number }}</h1>
            <StatusBadge :status="getStatusColor(inbound?.status)" :label="inbound?.status" class="uppercase text-xs" />
          </div>
          <p class="text-sm text-gray-500 mt-0.5">Gudang: {{ inbound?.warehouse?.name }} &middot; Tipe: {{ inbound?.source_type }}</p>
        </div>
      </div>
      <div class="flex gap-2" v-if="!loading">
        <button v-if="inbound?.status === 'pending'" @click="receiveAll" class="btn btn-primary shadow-sm" :disabled="processing">
          Terima Barang (Receive All)
        </button>
        <button v-if="inbound?.status === 'pending'" @click="cancelInbound" class="btn btn-outline border-red-200 text-red-600" :disabled="processing">
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
        <h3 class="font-bold text-gray-800 mb-4">Informasi Inbound</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-xs text-gray-500 mb-1">Referensi Sumber</p>
            <p class="font-medium text-gray-800">{{ inbound?.source_reference || '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Tanggal Harapan</p>
            <p class="font-medium text-gray-800">{{ formatDate(inbound?.expected_date) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Tanggal Diterima</p>
            <p class="font-medium text-gray-800">{{ formatDate(inbound?.received_date) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Dibuat Oleh</p>
            <p class="font-medium text-gray-800">{{ inbound?.user?.name || '-' }}</p>
          </div>
        </div>
        <div v-if="inbound?.notes" class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-xs text-gray-500 mb-1">Catatan</p>
          <p class="text-sm text-gray-700">{{ inbound.notes }}</p>
        </div>
      </div>

      <!-- Items Table -->
      <div class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-bold text-gray-800">Daftar Item ({{ items.length }})</h3>
          <button v-if="inbound?.status === 'pending'" @click="addItem" class="btn btn-sm btn-outline">
            + Tambah Item
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">Produk</th>
                <th class="p-4 text-center">Qty Diharap</th>
                <th class="p-4 text-center">Qty Diterima</th>
                <th class="p-4">Batch/Lot</th>
                <th class="p-4">Expiry</th>
                <th class="p-4 text-center w-20" v-if="inbound?.status === 'pending'">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="items.length === 0">
                <td :colspan="inbound?.status === 'pending' ? 6 : 5" class="p-8 text-center text-gray-400">Tidak ada item.</td>
              </tr>
              <tr v-for="(item, idx) in items" :key="idx" class="border-b border-gray-50 hover:bg-gray-50/50">
                <td class="p-4">
                  <p class="font-medium text-gray-800">{{ item.product?.name || 'Produk #' + item.product_id }}</p>
                  <p class="text-xs text-gray-500">{{ item.product?.sku || '' }}</p>
                </td>
                <td class="p-4 text-center font-semibold text-gray-800">{{ item.qty }}</td>
                <td class="p-4 text-center">
                  <span :class="item.received_qty ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                    {{ item.received_qty ?? '-' }}
                  </span>
                </td>
                <td class="p-4 text-sm text-gray-600 font-mono">{{ item.batch_number || '-' }}</td>
                <td class="p-4 text-sm text-gray-600">{{ formatDate(item.expiry_date) }}</td>
                <td class="p-4 text-center" v-if="inbound?.status === 'pending'">
                  <button @click="removeItem(idx)" class="text-red-400 hover:text-red-600">
                    <TrashIcon class="w-5 h-5 mx-auto" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add Item Modal -->
      <Modal v-model="showAddItemModal" title="Tambah Item Baru" size="md">
        <div class="space-y-4">
          <div>
            <label class="label">Produk <span class="text-red-500">*</span></label>
            <select v-model="newItem.product_id" class="input w-full">
              <option value="">-- Pilih Produk --</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="label">Kuantitas</label>
              <input v-model.number="newItem.qty" type="number" class="input text-center" min="1" />
            </div>
            <div>
              <label class="label">Batch/Lot</label>
              <input v-model="newItem.batch_number" type="text" class="input" placeholder="LOT-..." />
            </div>
            <div>
              <label class="label">Expiry</label>
              <input v-model="newItem.expiry_date" type="date" class="input" />
            </div>
          </div>
        </div>
        <template #footer>
          <div class="flex justify-end gap-3">
            <button @click="showAddItemModal = false" class="btn btn-outline">Batal</button>
            <button @click="confirmAddItem" class="btn btn-primary" :disabled="!newItem.product_id || newItem.qty < 1">Tambah</button>
          </div>
        </template>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { inboundAPI, productAPI } from '../services/api'
import { useInboundStore } from '../stores/inbound'
import { useNotificationStore } from '../stores/notification'
import StatusBadge from '../components/common/StatusBadge.vue'
import Modal from '../components/common/Modal.vue'
import { ArrowLeftIcon, ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id as idLocale } from 'date-fns/locale'

const route = useRoute()
const store = useInboundStore()
const notify = useNotificationStore()

const inbound = ref(null)
const items = ref([])
const products = ref([])
const loading = ref(false)
const processing = ref(false)
const showAddItemModal = ref(false)
const newItem = ref({ product_id: '', qty: 1, batch_number: '', expiry_date: '' })

function getStatusColor(status) {
  const map = { 'pending': 'warning', 'received': 'success', 'cancelled': 'danger' }
  return map[status?.toLowerCase()] || 'inactive'
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try { return format(new Date(dateStr), 'd MMM yyyy', { locale: idLocale }) } catch { return dateStr }
}

async function fetchProducts() {
  try {
    const res = await productAPI.list({ per_page: 500, is_active: 1 })
    products.value = res.data?.data || res.data || []
    if (Array.isArray(res)) products.value = res
  } catch (e) { console.error(e) }
}

async function fetchData() {
  loading.value = true
  try {
    const res = await inboundAPI.show(route.params.id)
    inbound.value = res.data?.data || res.data || res
    items.value = inbound.value.items ? JSON.parse(JSON.stringify(inbound.value.items)) : []
  } catch (error) {
    notify.error('Gagal memuat detail inbound')
  } finally {
    loading.value = false
  }
}

function addItem() {
  newItem.value = { product_id: '', qty: 1, batch_number: '', expiry_date: '' }
  showAddItemModal.value = true
}

async function confirmAddItem() {
  // For now, just add to local list. Backend update on save.
  const prod = products.value.find(p => p.id === newItem.value.product_id)
  items.value.push({
    ...newItem.value,
    product: prod || { name: 'Produk #' + newItem.value.product_id, sku: '' }
  })
  showAddItemModal.value = false
  notify.success('Item ditambahkan')
}

function removeItem(idx) {
  items.value.splice(idx, 1)
}

async function receiveAll() {
  if (!confirm('Terima seluruh item sesuai kuantitas yang diharapkan?')) return
  processing.value = true
  try {
    await store.receive(inbound.value.id, { notes: 'Received via detail page' })
    await fetchData()
  } catch (e) {
    // handled by store
  } finally {
    processing.value = false
  }
}

async function cancelInbound() {
  if (!confirm('Batalkan inbound ini?')) return
  processing.value = true
  try {
    await inboundAPI.cancel(inbound.value.id)
    notify.success('Inbound dibatalkan')
    await fetchData()
  } catch (e) {
    notify.error('Gagal membatalkan inbound')
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  fetchProducts()
  fetchData()
})
</script>
