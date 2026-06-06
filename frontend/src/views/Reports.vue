<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Pusat Laporan</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Laporan'}]" class="mt-1" />
      </div>
      <div class="flex gap-2">
        <select v-model="selectedWarehouse" class="input shadow-sm" @change="fetchData">
          <option value="">Semua Gudang</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
        <button @click="fetchData" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow">
          <ArrowPathIcon class="w-4 h-4" :class="{'animate-spin': loading}" /> Refresh
        </button>
      </div>
    </div>

    <!-- Overview Tabs -->
    <div class="flex border-b border-gray-200">
      <button 
        v-for="tab in tabs" 
        :key="tab.id" 
        @click="activeTab = tab.id"
        class="py-3 px-6 text-sm font-medium border-b-2 transition-colors"
        :class="activeTab === tab.id ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="py-12 flex justify-center">
      <ArrowPathIcon class="w-8 h-8 animate-spin text-blue-600" />
    </div>

    <div v-else>
      <!-- TAB 1: VALUASI STOK -->
      <div v-if="activeTab === 'valuation'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="card p-6 border-l-4 border-l-emerald-500">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Nilai Finansial</p>
            <p class="text-3xl font-bold text-gray-800">Rp {{ formatNumber(valuationData.total_value) }}</p>
          </div>
          <div class="card p-6 border-l-4 border-l-blue-500">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Kuantitas Fisik</p>
            <p class="text-3xl font-bold text-gray-800">{{ formatNumber(valuationData.total_quantity) }}</p>
          </div>
          <div class="card p-6 border-l-4 border-l-purple-500">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">SKU Unik Tersedia</p>
            <p class="text-3xl font-bold text-gray-800">{{ formatNumber(valuationData.total_products) }}</p>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-gray-800 mb-5">Distribusi Nilai Berdasarkan Kategori</h3>
          <div v-if="valuationData.by_category?.length === 0" class="text-center py-6 text-gray-500">Belum ada data.</div>
          <div class="space-y-5">
            <div v-for="(cat, idx) in valuationData.by_category" :key="idx" class="relative">
              <div class="flex justify-between text-sm mb-1.5">
                <span class="font-medium text-gray-700">{{ cat.category }} ({{ formatNumber(cat.quantity) }} unit)</span>
                <span class="font-bold text-gray-800">Rp {{ formatNumber(cat.value) }}</span>
              </div>
              <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="bg-blue-500 h-full rounded-full transition-all duration-1000" :style="{ width: (cat.value / valuationData.total_value * 100) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: UTILISASI GUDANG -->
      <div v-if="activeTab === 'utilization'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-for="wh in utilizationData" :key="wh.warehouse.id" class="card p-6 border-t-4 border-t-indigo-500">
            <div class="flex justify-between items-start mb-6">
              <div>
                <h3 class="font-bold text-gray-800 text-lg">{{ wh.warehouse.name }}</h3>
                <p class="text-sm text-gray-500 font-mono">{{ wh.warehouse.code }}</p>
              </div>
              <div class="text-right">
                <span class="text-2xl font-bold" :class="wh.utilization > (wh.total_slots * 0.8) ? 'text-red-600' : 'text-emerald-600'">
                  {{ wh.total_slots > 0 ? Math.round((wh.utilization / wh.total_slots) * 100) : 0 }}%
                </span>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Tingkat Pengisian</p>
              </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 mb-6 text-center">
              <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                <p class="text-xs text-gray-500 mb-1">Zona</p>
                <p class="font-bold text-gray-800">{{ wh.total_zones }}</p>
              </div>
              <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                <p class="text-xs text-gray-500 mb-1">Rak</p>
                <p class="font-bold text-gray-800">{{ wh.total_racks }}</p>
              </div>
              <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                <p class="text-xs text-gray-500 mb-1">Total Slot</p>
                <p class="font-bold text-gray-800">{{ wh.total_slots }}</p>
              </div>
            </div>

            <div class="h-3 bg-gray-100 rounded-full overflow-hidden shadow-inner">
              <div :class="wh.utilization > (wh.total_slots * 0.8) ? 'bg-red-500' : 'bg-emerald-500'" class="h-full rounded-full" :style="{ width: (wh.utilization / (wh.total_slots || 1) * 100) + '%' }"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-500">
              <span>{{ wh.utilization }} slot terisi</span>
              <span>{{ wh.total_slots - wh.utilization }} slot kosong</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: MUTASI STOK -->
      <div v-if="activeTab === 'mutations'" class="card">
        <div class="p-4 border-b border-gray-100">
          <h3 class="font-bold text-gray-800">Riwayat Pergerakan Barang</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">Tanggal</th>
                <th class="p-4">Produk</th>
                <th class="p-4">Gudang</th>
                <th class="p-4">Tipe Mutasi</th>
                <th class="p-4 text-right">Kuantitas</th>
                <th class="p-4">Oleh</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="mutationsData.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-500">Tidak ada riwayat mutasi.</td>
              </tr>
              <tr v-for="m in mutationsData" :key="m.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors text-sm">
                <td class="p-4 text-gray-600">{{ formatDate(m.created_at) }}</td>
                <td class="p-4">
                  <p class="font-medium text-gray-800">{{ m.product?.name }}</p>
                  <p class="text-xs text-gray-500 font-mono">{{ m.product?.sku }}</p>
                </td>
                <td class="p-4 text-gray-600">{{ m.warehouse?.name }}</td>
                <td class="p-4">
                  <span :class="m.type === 'in' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200'" class="px-2 py-1 rounded text-xs font-medium border uppercase">
                    {{ m.type }} ({{ m.reference_type }})
                  </span>
                </td>
                <td class="p-4 text-right font-semibold" :class="m.type === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                  {{ m.type === 'in' ? '+' : '-' }}{{ formatNumber(m.quantity) }}
                </td>
                <td class="p-4 text-gray-600">{{ m.user?.name || 'Sistem' }}</td>
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
import BreadCrumb from '../components/common/BreadCrumb.vue'
import { reportAPI, warehouseAPI } from '../services/api'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const tabs = [
  { id: 'valuation', label: 'Valuasi Stok' },
  { id: 'utilization', label: 'Utilisasi Gudang' },
  { id: 'mutations', label: 'Mutasi Historis' },
]

const activeTab = ref('valuation')
const loading = ref(false)
const selectedWarehouse = ref('')
const warehouses = ref([])

const valuationData = ref({ total_quantity: 0, total_value: 0, total_products: 0, by_category: [] })
const utilizationData = ref([])
const mutationsData = ref([])

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num || 0)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try { return format(new Date(dateStr), 'dd MMM yyyy HH:mm', { locale: id }) } catch { return dateStr }
}

async function fetchWarehouses() {
  try {
    const res = await warehouseAPI.list({ per_page: 100 })
    warehouses.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) {
    console.error(e)
  }
}

async function fetchData() {
  loading.value = true
  try {
    const params = selectedWarehouse.value ? { warehouse_id: selectedWarehouse.value } : {}
    
    // Fetch Valuation
    const valRes = await reportAPI.valuation(params)
    valuationData.value = valRes.data || valRes
    
    // Fetch Utilization
    const utilRes = await reportAPI.warehouseUtilization(params)
    utilizationData.value = utilRes.data || utilRes

    // Fetch Mutations
    const mutRes = await reportAPI.movement({ ...params, per_page: 50 })
    mutationsData.value = mutRes.data || mutRes
  } catch (error) {
    console.error('Error fetching reports:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchWarehouses()
  fetchData()
})
</script>
