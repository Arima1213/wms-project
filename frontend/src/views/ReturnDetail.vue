<template>
  <div class="p-6" v-if="ret">
    <div class="flex items-center gap-2 mb-6">
      <router-link to="/returns" class="text-blue-400 hover:text-blue-300 text-sm">← Retur</router-link>
      <span class="text-slate-600">/</span>
      <h1 class="text-2xl font-bold text-white">{{ ret.return_number }}</h1>
      <span :class="statusClass(ret.status)" class="px-2 py-1 rounded-full text-xs font-medium ml-2">{{ statusLabel(ret.status) }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Informasi</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Tipe:</span> {{ typeLabel(ret.type) }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Gudang:</span> {{ ret.warehouse?.name || '-' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Tanggal:</span> {{ ret.return_date || '-' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Alasan:</span> {{ ret.reason || '-' }}</p>
      </div>
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Referensi</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Customer:</span> {{ ret.customer?.name || '-' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Supplier:</span> {{ ret.supplier?.name || '-' }}</p>
      </div>
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Status</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Dibuat oleh:</span> {{ ret.creator?.name || '-' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Diproses:</span> {{ ret.processed_date || '-' }}</p>
      </div>
    </div>

    <!-- Items -->
    <div class="bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden mb-6">
      <div class="px-4 py-3 bg-slate-700/50 border-b border-slate-700">
        <h3 class="text-sm font-medium text-white">Item Retur</h3>
      </div>
      <table class="w-full">
        <thead class="bg-slate-700/30">
          <tr>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Produk</th>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Qty</th>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Kondisi</th>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Resolusi</th>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Refund</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <tr v-for="it in ret.items" :key="it.id" class="hover:bg-slate-700/30">
            <td class="px-4 py-2 text-sm text-slate-300">{{ it.product?.sku || '-' }} - {{ it.product?.name || '-' }}</td>
            <td class="px-4 py-2 text-sm text-slate-300">{{ it.quantity }}</td>
            <td class="px-4 py-2 text-sm text-slate-300">{{ conditionLabel(it.condition) }}</td>
            <td class="px-4 py-2 text-sm text-slate-300">{{ resolutionLabel(it.resolution) }}</td>
            <td class="px-4 py-2 text-sm text-slate-300">{{ it.refund_amount ? formatMoney(it.refund_amount) : '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Notes -->
    <div v-if="ret.notes" class="bg-slate-800/50 rounded-xl border border-slate-700 p-4 mb-6">
      <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Catatan</h3>
      <p class="text-sm text-slate-300 whitespace-pre-wrap">{{ ret.notes }}</p>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button v-if="ret.status === 'draft'" @click="submitReturn" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
        Submit
      </button>
      <button v-if="ret.status === 'pending'" @click="approveReturn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
        Approve
      </button>
      <button v-if="ret.status === 'approved'" @click="processReturn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        Proses (Restok)
      </button>
      <button v-if="['pending', 'approved'].includes(ret.status)" @click="rejectReturn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
        Tolak
      </button>
      <button v-if="!['processed', 'cancelled'].includes(ret.status)" @click="cancelReturn" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
        Batalkan
      </button>
    </div>
  </div>
  <div v-else class="p-6 text-center text-slate-400">Memuat...</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useReturnStore } from '../stores/return'

const route = useRoute()
const router = useRouter()
const returnStore = useReturnStore()

const ret = computed(() => returnStore.item)

function typeLabel(t) { return { customer_return: 'Customer Return', supplier_return: 'Supplier Return', internal: 'Internal' }[t] || t }
function statusLabel(s) { return { draft: 'Draft', pending: 'Pending', approved: 'Approved', rejected: 'Ditolak', processed: 'Diproses', cancelled: 'Dibatalkan' }[s] || s }
function statusClass(s) { return { draft: 'bg-slate-600 text-slate-200', pending: 'bg-yellow-600 text-yellow-100', approved: 'bg-green-600 text-green-100', rejected: 'bg-red-600 text-red-100', processed: 'bg-blue-600 text-blue-100', cancelled: 'bg-gray-600 text-gray-200' }[s] || 'bg-slate-600' }
function conditionLabel(c) { return { good: 'Baik', damaged: 'Rusak', expired: 'Kadaluarsa', defective: 'Cacat' }[c] || c }
function resolutionLabel(r) { return { restock: 'Restok', discard: 'Buang', return_to_supplier: 'Retur Supplier' }[r] || r }
function formatMoney(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(v) }

async function submitReturn() { await returnStore.submitReturn(ret.value.id); await returnStore.fetchReturn(route.params.id) }
async function approveReturn() { await returnStore.approveReturn(ret.value.id); await returnStore.fetchReturn(route.params.id) }
async function processReturn() { await returnStore.processReturn(ret.value.id); await returnStore.fetchReturn(route.params.id) }
async function rejectReturn() { const r = prompt('Alasan penolakan:'); await returnStore.rejectReturn(ret.value.id, r || ''); await returnStore.fetchReturn(route.params.id) }
async function cancelReturn() { if (confirm('Batalkan retur ini?')) { await returnStore.cancelReturn(ret.value.id); await returnStore.fetchReturn(route.params.id) } }

onMounted(() => { returnStore.fetchReturn(route.params.id) })
</script>
