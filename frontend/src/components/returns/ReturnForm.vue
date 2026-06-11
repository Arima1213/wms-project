<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Tipe Retur *</label>
        <select v-model="form.type" required class="w-full px-3 py-2 border rounded-lg text-sm">
          <option value="">Pilih tipe</option>
          <option value="customer_return">Retur Customer</option>
          <option value="supplier_return">Retur Supplier</option>
          <option value="internal">Internal</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Gudang *</label>
        <select v-model="form.warehouse_id" required class="w-full px-3 py-2 border rounded-lg text-sm">
          <option value="">Pilih gudang</option>
          <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Customer</label>
        <select v-model="form.customer_id" class="w-full px-3 py-2 border rounded-lg text-sm">
          <option value="">Pilih customer</option>
          <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Supplier</label>
        <select v-model="form.supplier_id" class="w-full px-3 py-2 border rounded-lg text-sm">
          <option value="">Pilih supplier</option>
          <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Tanggal Retur</label>
        <input type="date" v-model="form.return_date" class="w-full px-3 py-2 border rounded-lg text-sm" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Alasan</label>
        <input type="text" v-model="form.reason" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Alasan retur" />
      </div>
    </div>

    <!-- Items -->
    <div>
      <div class="flex justify-between items-center mb-2">
        <label class="block text-sm font-medium">Item Retur *</label>
        <button type="button" @click="addItem" class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded">+ Tambah Item</button>
      </div>
      <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 items-start mb-2">
        <select v-model="item.product_id" required class="flex-1 px-3 py-2 border rounded-lg text-sm">
          <option value="">Pilih produk</option>
          <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
        </select>
        <input type="number" v-model="item.quantity" required step="0.01" min="0.01" placeholder="Qty" class="w-24 px-3 py-2 border rounded-lg text-sm" />
        <select v-model="item.condition" class="w-28 px-3 py-2 border rounded-lg text-sm">
          <option value="good">Baik</option>
          <option value="damaged">Rusak</option>
          <option value="expired">Kadaluarsa</option>
          <option value="defective">Cacat</option>
        </select>
        <select v-model="item.resolution" class="w-36 px-3 py-2 border rounded-lg text-sm">
          <option value="restock">Restock</option>
          <option value="discard">Buang</option>
          <option value="return_to_supplier">Kembali Supplier</option>
        </select>
        <button type="button" @click="removeItem(i)" class="px-2 py-2 text-red-500 hover:text-red-700">✕</button>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Catatan</label>
      <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Catatan tambahan"></textarea>
    </div>

    <div class="flex justify-end gap-3">
      <button type="button" @click="$emit('cancel')" class="px-4 py-2 border rounded-lg text-sm">Batal</button>
      <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
        {{ saving ? 'Menyimpan...' : 'Simpan' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useReturnStore } from '../../stores/returns'
import api from '../../services/api'

const emit = defineEmits(['saved', 'cancel'])
const store = useReturnStore()
const saving = ref(false)
const warehouses = ref([])
const customers = ref([])
const suppliers = ref([])
const products = ref([])

const form = reactive({
  warehouse_id: '',
  customer_id: '',
  supplier_id: '',
  type: '',
  reason: '',
  return_date: new Date().toISOString().split('T')[0],
  notes: '',
  items: [{ product_id: '', quantity: 1, condition: 'good', resolution: 'restock', refund_amount: 0, notes: '' }],
})

function addItem() {
  form.items.push({ product_id: '', quantity: 1, condition: 'good', resolution: 'restock', refund_amount: 0, notes: '' })
}

function removeItem(i) {
  if (form.items.length > 1) form.items.splice(i, 1)
}

async function submit() {
  saving.value = true
  try {
    const res = await store.create(form)
    alert(res.message || 'Return berhasil dibuat')
    emit('saved')
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan return')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const [wRes, cuRes, suRes, pRes] = await Promise.all([
      api.get('/v1/warehouses'),
      api.get('/v1/customers').catch(() => ({ data: { data: [] } })),
      api.get('/v1/suppliers').catch(() => ({ data: { data: [] } })),
      api.get('/v1/products', { params: { per_page: 200 } }),
    ])
    warehouses.value = wRes.data.data || []
    customers.value = cuRes.data.data || []
    suppliers.value = suRes.data.data || []
    products.value = pRes.data.data || []
  } catch (e) {
    // Silently fail - form still usable
  }
})
</script>
