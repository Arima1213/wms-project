<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-white">Retur Barang</h1>
        <p class="text-sm text-slate-400 mt-1">Kelola retur barang masuk dari customer / ke supplier</p>
      </div>
      <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        + Retur Baru
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-3 mb-4 flex-wrap">
      <select v-model="filters.status" @change="applyFilters" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white">
        <option value="">Semua Status</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="processed">Processed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="filters.type" @change="applyFilters" class="px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white">
        <option value="">Semua Tipe</option>
        <option value="customer_return">Customer Return</option>
        <option value="supplier_return">Supplier Return</option>
        <option value="internal">Internal</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-slate-400">Memuat...</div>
      <table v-else-if="items.length" class="w-full">
        <thead class="bg-slate-700/50">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">No. Retur</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Tipe</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Gudang</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Status</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Tanggal</th>
            <th class="text-left px-4 py-3 text-xs font-medium text-slate-400 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <tr v-for="ret in items" :key="ret.id" class="hover:bg-slate-700/30 transition-colors">
            <td class="px-4 py-3">
              <router-link :to="`/returns/${ret.id}`" class="text-blue-400 hover:text-blue-300 font-medium">
                {{ ret.return_number }}
              </router-link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-300">{{ typeLabel(ret.type) }}</td>
            <td class="px-4 py-3 text-sm text-slate-300">{{ ret.warehouse?.name || '-' }}</td>
            <td class="px-4 py-3">
              <span :class="statusClass(ret.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ statusLabel(ret.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-300">{{ ret.return_date || '-' }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-1">
                <router-link :to="`/returns/${ret.id}`" class="px-2 py-1 text-xs bg-slate-700 rounded hover:bg-slate-600 text-slate-300">Detail</router-link>
                <button v-if="ret.status === 'draft'" @click="confirmSubmit(ret)" class="px-2 py-1 text-xs bg-yellow-600 rounded hover:bg-yellow-700 text-white">Submit</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="p-8 text-center text-slate-500">Belum ada data retur.</div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last > 1" class="flex justify-center gap-2 mt-4">
      <button @click="changePage(pagination.current - 1)" :disabled="pagination.current <= 1"
        class="px-3 py-1 bg-slate-700 rounded text-sm disabled:opacity-50">Prev</button>
      <span class="px-3 py-1 text-sm text-slate-400">{{ pagination.current }} / {{ pagination.last }}</span>
      <button @click="changePage(pagination.current + 1)" :disabled="pagination.current >= pagination.last"
        class="px-3 py-1 bg-slate-700 rounded text-sm disabled:opacity-50">Next</button>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showCreateModal = false">
      <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <h2 class="text-lg font-bold text-white mb-4">Buat Retur Baru</h2>
        <form @submit.prevent="handleCreate">
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm text-slate-400 mb-1">Gudang *</label>
              <select v-model="form.warehouse_id" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="">Pilih Gudang</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Tipe *</label>
              <select v-model="form.type" required class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="customer_return">Customer Return</option>
                <option value="supplier_return">Supplier Return</option>
                <option value="internal">Internal</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Tanggal Retur</label>
              <input v-model="form.return_date" type="date" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" />
            </div>
            <div>
              <label class="block text-sm text-slate-400 mb-1">Alasan</label>
              <input v-model="form.reason" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" placeholder="Alasan retur" />
            </div>
          </div>
          <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Catatan</label>
            <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" placeholder="Catatan"></textarea>
          </div>

          <!-- Items -->
          <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-medium text-white">Item Retur</h3>
              <button type="button" @click="addItem" class="text-xs text-blue-400 hover:text-blue-300">+ Tambah Item</button>
            </div>
            <div v-for="(item, idx) in form.items" :key="idx" class="flex gap-2 mb-2 items-start">
              <select v-model="item.product_id" required class="flex-1 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="">Pilih Produk</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }}</option>
              </select>
              <input v-model="item.quantity" type="number" step="0.01" min="0.01" required placeholder="Qty"
                class="w-24 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white" />
              <select v-model="item.condition" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="good">Baik</option>
                <option value="damaged">Rusak</option>
                <option value="expired">Kadaluarsa</option>
                <option value="defective">Cacat</option>
              </select>
              <select v-model="item.resolution" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-white">
                <option value="restock">Restok</option>
                <option value="discard">Buang</option>
                <option value="return_to_supplier">Retur Supplier</option>
              </select>
              <button type="button" @click="removeItem(idx)" class="p-2 text-red-400 hover:text-red-300">✕</button>
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-700 rounded-lg text-sm text-slate-300 hover:bg-slate-600">Batal</button>
            <button type="submit" :disabled="loading" class="px-4 py-2 bg-blue-600 rounded-lg text-sm text-white hover:bg-blue-700 disabled:opacity-50">
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useReturnStore } from '../stores/return'
import { useWarehouseStore } from '../stores/warehouse'
import { useProductStore } from '../stores/product'

const returnStore = useReturnStore()
const warehouseStore = useWarehouseStore()
const productStore = useProductStore()

const items = computed(() => returnStore.items)
const pagination = computed(() => returnStore.pagination)
const loading = computed(() => returnStore.loading)
const warehouses = computed(() => warehouseStore.items)
const products = computed(() => productStore.items)

const filters = reactive({ status: '', type: '', warehouse_id: '' })
const showCreateModal = ref(false)

const form = reactive({
  warehouse_id: '', type: 'customer_return', reason: '', notes: '',
  return_date: new Date().toISOString().split('T')[0], items: [],
})

function addItem() {
  form.items.push({ product_id: '', quantity: 1, condition: 'good', resolution: 'restock', refund_amount: 0, notes: '' })
}
function removeItem(idx) { form.items.splice(idx, 1) }

async function applyFilters() {
  returnStore.filters = { ...filters }
  await returnStore.fetchReturns()
}

async function changePage(page) {
  await returnStore.fetchReturns({ page })
}

function typeLabel(t) {
  const m = { customer_return: 'Customer Return', supplier_return: 'Supplier Return', internal: 'Internal' }
  return m[t] || t
}
function statusLabel(s) {
  const m = { draft: 'Draft', pending: 'Pending', approved: 'Approved', rejected: 'Ditolak', processed: 'Diproses', cancelled: 'Dibatalkan' }
  return m[s] || s
}
function statusClass(s) {
  const m = { draft: 'bg-slate-600 text-slate-200', pending: 'bg-yellow-600 text-yellow-100', approved: 'bg-green-600 text-green-100', rejected: 'bg-red-600 text-red-100', processed: 'bg-blue-600 text-blue-100', cancelled: 'bg-gray-600 text-gray-200' }
  return m[s] || 'bg-slate-600'
}

async function handleCreate() {
  try {
    await returnStore.createReturn({ ...form })
    showCreateModal.value = false
    form.items = []
    await returnStore.fetchReturns()
  } catch (e) {
    alert('Gagal menyimpan retur')
  }
}

function confirmSubmit(ret) {
  if (confirm(`Submit retur ${ret.return_number}?`)) {
    returnStore.submitReturn(ret.id).then(() => returnStore.fetchReturns())
  }
}

onMounted(async () => {
  await Promise.all([
    returnStore.fetchReturns(),
    warehouseStore.fetchWarehouses(),
    productStore.fetchProducts(),
  ])
})
</script>
