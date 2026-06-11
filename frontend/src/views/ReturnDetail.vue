<template>
  <div class="p-6">
    <div class="mb-4">
      <button @click="$router.push('/returns')" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke Retur</button>
    </div>

    <div v-if="store.current" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Info Card -->
      <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h1 class="text-2xl font-bold">{{ store.current.return_number }}</h1>
            <p class="text-sm text-gray-500">Dibuat {{ store.current.created_at }}</p>
          </div>
          <StatusBadge :status="store.current.status" />
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <div>
            <p class="text-sm text-gray-500">Tipe</p>
            <p class="font-medium">{{ typeLabel(store.current.type) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Gudang</p>
            <p class="font-medium">{{ store.current.warehouse?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Customer</p>
            <p class="font-medium">{{ store.current.customer?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Supplier</p>
            <p class="font-medium">{{ store.current.supplier?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Tanggal Retur</p>
            <p class="font-medium">{{ store.current.return_date || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Nilai Refund</p>
            <p class="font-medium">Rp {{ formatNumber(store.current.refund_amount) }}</p>
          </div>
        </div>

        <div v-if="store.current.notes" class="mb-6">
          <p class="text-sm text-gray-500">Catatan</p>
          <p class="text-sm whitespace-pre-wrap">{{ store.current.notes }}</p>
        </div>

        <!-- Items Table -->
        <h3 class="font-semibold mb-2">Item Retur</h3>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="text-left py-2">Produk</th>
              <th class="text-right py-2">Qty</th>
              <th class="text-center py-2">Kondisi</th>
              <th class="text-center py-2">Resolusi</th>
              <th class="text-right py-2">Refund</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in store.current.items" :key="item.id" class="border-b">
              <td class="py-2">{{ item.product?.name || '-' }} <span class="text-gray-400">({{ item.product?.sku }})</span></td>
              <td class="text-right py-2">{{ item.quantity }}</td>
              <td class="text-center py-2">{{ conditionLabel(item.condition) }}</td>
              <td class="text-center py-2">{{ resolutionLabel(item.resolution) }}</td>
              <td class="text-right py-2">Rp {{ formatNumber(item.refund_amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Actions Card -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-4">Aksi</h3>
        <div class="space-y-2">
          <button v-if="store.current.status === 'pending'" @click="doAction('approve')" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Setujui</button>
          <button v-if="store.current.status === 'pending'" @click="doReject" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Tolak</button>
          <button v-if="store.current.status === 'approved'" @click="doAction('process')" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Proses (Restock)</button>
          <button v-if="['draft', 'pending', 'approved'].includes(store.current.status)" @click="doAction('cancel')" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Batalkan</button>
        </div>

        <div v-if="store.current.processor" class="mt-6 pt-4 border-t">
          <p class="text-sm text-gray-500">Diproses oleh</p>
          <p class="font-medium">{{ store.current.processor?.name }}</p>
          <p v-if="store.current.processed_date" class="text-xs text-gray-400">{{ store.current.processed_date }}</p>
        </div>
      </div>
    </div>

    <div v-else-if="store.loading" class="text-center py-12 text-gray-500">Memuat data...</div>
    <div v-else class="text-center py-12 text-gray-500">Data tidak ditemukan</div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useReturnStore } from '../stores/returns'
import StatusBadge from '../components/common/StatusBadge.vue'

const route = useRoute()
const store = useReturnStore()

onMounted(async () => {
  await store.fetchDetail(route.params.id)
})

function typeLabel(type) {
  return { customer_return: 'Retur Customer', supplier_return: 'Retur Supplier', internal: 'Internal' }[type] || type
}

function conditionLabel(c) {
  return { good: 'Baik', damaged: 'Rusak', expired: 'Kadaluarsa', defective: 'Cacat' }[c] || c
}

function resolutionLabel(r) {
  return { restock: 'Restock', discard: 'Buang', return_to_supplier: 'Kembali Supplier' }[r] || r
}

function formatNumber(n) {
  return Number(n || 0).toLocaleString('id-ID')
}

async function doAction(action) {
  if (!confirm(`Yakin ingin ${actionLabel(action)} retur ini?`)) return
  try {
    const fn = {
      approve: () => store.approve(route.params.id),
      process: () => store.process(route.params.id),
      cancel: () => store.cancel(route.params.id),
    }[action]
    const res = await fn()
    alert(res.message)
    await store.fetchDetail(route.params.id)
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal')
  }
}

async function doReject() {
  const reason = prompt('Alasan penolakan (opsional):')
  if (reason === null) return
  if (!confirm('Yakin ingin menolak retur ini?')) return
  try {
    const res = await store.reject(route.params.id, reason || undefined)
    alert(res.message)
    await store.fetchDetail(route.params.id)
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal')
  }
}

function actionLabel(action) {
  return { approve: 'menyetujui', reject: 'menolak', process: 'memproses', cancel: 'membatalkan' }[action]
}
</script>
