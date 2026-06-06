<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button @click="$router.push('/stock-opnames')" class="btn btn-sm btn-ghost p-2 -ml-2">
          <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">Detail Opname: {{ opname?.opname_number }}</h1>
            <StatusBadge :status="getStatusColor(opname?.status)" :label="opname?.status" class="uppercase text-xs" />
          </div>
          <p class="text-sm text-gray-500 mt-0.5">Gudang: {{ opname?.warehouse?.name }}</p>
        </div>
      </div>
      <div class="flex gap-2" v-if="!loading">
        <button v-if="opname?.status === 'draft'" @click="startOpname" class="btn btn-primary shadow-sm" :disabled="processing">
          Mulai Proses Opname
        </button>
        <button v-if="opname?.status === 'in_progress'" @click="saveItems" class="btn btn-outline shadow-sm" :disabled="processing">
          Simpan Progress
        </button>
        <button v-if="opname?.status === 'in_progress'" @click="submitOpname" class="btn btn-primary shadow-sm" :disabled="processing">
          Submit untuk Persetujuan
        </button>
        <button v-if="opname?.status === 'submitted'" @click="approveOpname" class="btn btn-emerald shadow-sm" :disabled="processing">
          Approve (Finalisasi)
        </button>
      </div>
    </div>

    <div v-if="loading" class="py-12 flex justify-center">
      <ArrowPathIcon class="w-8 h-8 animate-spin text-blue-600" />
    </div>

    <div v-else class="space-y-6">
      <div class="card p-6">
        <h3 class="font-bold text-gray-800 mb-4">Catatan Tambahan</h3>
        <textarea v-model="opname.notes" class="input w-full resize-none" rows="2" :disabled="opname?.status !== 'draft' && opname?.status !== 'in_progress'"></textarea>
      </div>

      <div class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-bold text-gray-800">Daftar Item Fisik</h3>
          <button v-if="opname?.status === 'in_progress'" @click="addItem" class="btn btn-sm btn-outline">
            + Tambah Baris
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">Produk</th>
                <th class="p-4 text-center">Kuantitas Sistem</th>
                <th class="p-4 text-center">Kuantitas Aktual</th>
                <th class="p-4 text-center">Selisih</th>
                <th class="p-4 text-center w-20">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="items.length === 0">
                <td colspan="5" class="p-8 text-center text-gray-500">Tidak ada item yang diopname.</td>
              </tr>
              <tr v-for="(item, idx) in items" :key="idx" class="border-b border-gray-50">
                <td class="p-4">
                  <div v-if="opname?.status === 'in_progress'">
                    <select v-model="item.product_id" class="input input-sm w-full" @change="onProductChange(item)">
                      <option value="">-- Pilih Produk --</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }}</option>
                    </select>
                  </div>
                  <div v-else>
                    <p class="font-medium">{{ item.product?.name }}</p>
                    <p class="text-xs text-gray-500">{{ item.product?.sku }}</p>
                  </div>
                </td>
                <td class="p-4 text-center">
                  <input v-if="opname?.status === 'in_progress'" type="number" v-model.number="item.system_qty" class="input input-sm w-24 text-center bg-gray-50" readonly />
                  <span v-else>{{ item.system_qty }}</span>
                </td>
                <td class="p-4 text-center">
                  <input v-if="opname?.status === 'in_progress'" type="number" v-model.number="item.actual_qty" class="input input-sm w-24 text-center" @input="calculateDiff(item)" />
                  <span v-else>{{ item.actual_qty }}</span>
                </td>
                <td class="p-4 text-center font-bold" :class="getDiffColor(item.difference_qty)">
                  {{ item.difference_qty > 0 ? '+' : '' }}{{ item.difference_qty }}
                </td>
                <td class="p-4 text-center">
                  <button v-if="opname?.status === 'in_progress'" @click="removeItem(idx)" class="text-red-500 hover:text-red-700">
                    <TrashIcon class="w-5 h-5 mx-auto" />
                  </button>
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
import { stockOpnameAPI, productAPI } from '../services/api'
import { useNotificationStore } from '../stores/notification'
import StatusBadge from '../components/common/StatusBadge.vue'
import { ArrowLeftIcon, ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const notify = useNotificationStore()

const opname = ref(null)
const items = ref([])
const products = ref([])
const loading = ref(false)
const processing = ref(false)

function getStatusColor(status) {
  const map = {
    'draft': 'inactive',
    'in_progress': 'warning',
    'submitted': 'info',
    'approved': 'success'
  }
  return map[status?.toLowerCase()] || 'inactive'
}

function getDiffColor(diff) {
  if (diff > 0) return 'text-emerald-600'
  if (diff < 0) return 'text-red-600'
  return 'text-gray-500'
}

async function fetchProducts() {
  try {
    const res = await productAPI.list({ per_page: 500, is_active: 1 })
    products.value = res.data?.data || res.data || []
  } catch (error) {
    console.error('Failed to load products')
  }
}

async function fetchData() {
  loading.value = true
  try {
    const res = await stockOpnameAPI.show(route.params.id)
    opname.value = res.data?.data || res.data
    items.value = opname.value.items ? JSON.parse(JSON.stringify(opname.value.items)) : []
  } catch (error) {
    notify.error('Gagal memuat detail opname')
  } finally {
    loading.value = false
  }
}

function addItem() {
  items.value.push({
    product_id: '',
    system_qty: 0,
    actual_qty: 0,
    difference_qty: 0
  })
}

function removeItem(idx) {
  items.value.splice(idx, 1)
}

function onProductChange(item) {
  // Mock system qty based on inventory - simplified for now
  item.system_qty = 0
  item.actual_qty = 0
  item.difference_qty = 0
}

function calculateDiff(item) {
  item.difference_qty = Number(item.actual_qty) - Number(item.system_qty)
}

async function startOpname() {
  processing.value = true
  try {
    await stockOpnameAPI.start(opname.value.id)
    notify.success('Opname dimulai')
    await fetchData()
  } catch (e) {
    notify.error('Gagal memulai opname')
  } finally {
    processing.value = false
  }
}

async function saveItems() {
  processing.value = true
  try {
    const payload = {
      notes: opname.value.notes,
      items: items.value.map(i => ({
        product_id: i.product_id,
        system_qty: i.system_qty,
        actual_qty: i.actual_qty,
        difference_qty: i.difference_qty
      }))
    }
    await stockOpnameAPI.update(opname.value.id, payload)
    notify.success('Progress berhasil disimpan')
    await fetchData()
  } catch (e) {
    notify.error('Gagal menyimpan progress')
  } finally {
    processing.value = false
  }
}

async function submitOpname() {
  await saveItems() // auto save before submit
  processing.value = true
  try {
    await stockOpnameAPI.submit(opname.value.id)
    notify.success('Opname dikirim untuk persetujuan')
    await fetchData()
  } catch (e) {
    notify.error('Gagal submit opname')
  } finally {
    processing.value = false
  }
}

async function approveOpname() {
  processing.value = true
  try {
    await stockOpnameAPI.approve(opname.value.id)
    notify.success('Opname disetujui. Inventori telah diperbarui.')
    await fetchData()
  } catch (e) {
    notify.error('Gagal menyetujui opname')
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  fetchProducts()
  fetchData()
})
</script>
