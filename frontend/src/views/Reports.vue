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
    <div class="flex border-b border-gray-200 overflow-x-auto">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="py-3 px-6 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
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
        <ValuationTab :data="valuationData" :format-number="formatNumber" />
      </div>

      <!-- TAB 2: UTILISASI GUDANG -->
      <div v-if="activeTab === 'utilization'" class="space-y-6">
        <UtilizationTab :data="utilizationData" />
      </div>

      <!-- TAB 3: MUTASI HISTORIS -->
      <div v-if="activeTab === 'mutations'" class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-bold text-gray-800">Riwayat Pergerakan Barang</h3>
          <ExportButtons :params="{ type: 'mutations', warehouse_id: selectedWarehouse }" @export="handleExport" />
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

      <!-- TAB 4: STOK LENGKAP -->
      <div v-if="activeTab === 'stock'" class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-gray-800">Laporan Stok Lengkap</h3>
            <p class="text-xs text-gray-500 mt-0.5">Seluruh posisi stok per produk & gudang</p>
          </div>
          <ExportButtons :params="{ type: 'stock', warehouse_id: selectedWarehouse }" @export="handleExport" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">SKU</th>
                <th class="p-4">Produk</th>
                <th class="p-4">Kategori</th>
                <th class="p-4">Gudang</th>
                <th class="p-4 text-right">Kuantitas</th>
                <th class="p-4 text-right">Unit Cost</th>
                <th class="p-4 text-right">Total Nilai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="stockData.length === 0">
                <td colspan="7" class="p-8 text-center text-gray-500">Belum ada data stok.</td>
              </tr>
              <tr v-for="item in stockData" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors text-sm">
                <td class="p-4 font-mono text-gray-500">{{ item.product?.sku }}</td>
                <td class="p-4 font-medium text-gray-800">{{ item.product?.name }}</td>
                <td class="p-4 text-gray-600">{{ item.product?.category?.name || '-' }}</td>
                <td class="p-4 text-gray-600">{{ item.warehouse?.name }}</td>
                <td class="p-4 text-right font-semibold">{{ formatNumber(item.quantity) }}</td>
                <td class="p-4 text-right text-gray-600">Rp {{ formatNumber(item.unit_cost) }}</td>
                <td class="p-4 text-right font-semibold">Rp {{ formatNumber(item.quantity * (item.unit_cost || 0)) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 5: AGING / BARANG LAMBAT -->
      <div v-if="activeTab === 'aging'" class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-gray-800">Analisa Aging Stok</h3>
            <p class="text-xs text-gray-500 mt-0.5">Produk yang tidak mengalami pergerakan</p>
          </div>
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-600">
              Ambang batas:
              <select v-model.number="agingDays" @change="fetchAging" class="input input-sm">
                <option :value="30">30 hari</option>
                <option :value="60">60 hari</option>
                <option :value="90">90 hari</option>
                <option :value="180">180 hari</option>
              </select>
            </label>
            <ExportButtons :params="{ type: 'aging', days: agingDays, warehouse_id: selectedWarehouse }" @export="handleExport" />
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">SKU</th>
                <th class="p-4">Produk</th>
                <th class="p-4">Gudang</th>
                <th class="p-4 text-right">Total Qty</th>
                <th class="p-4 text-right">Terakhir Bergerak</th>
                <th class="p-4 text-right">Hari Diam</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="agingData.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-500">Tidak ada produk yang melebihi ambang aging.</td>
              </tr>
              <tr v-for="(item, idx) in agingData" :key="idx" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors text-sm">
                <td class="p-4 font-mono text-gray-500">{{ item.sku }}</td>
                <td class="p-4 font-medium text-gray-800">{{ item.name }}</td>
                <td class="p-4 text-gray-600">{{ item.warehouse_code }}</td>
                <td class="p-4 text-right font-semibold">{{ formatNumber(item.total_qty) }}</td>
                <td class="p-4 text-right text-gray-600">{{ item.last_movement ? formatDate(item.last_movement) : '-' }}</td>
                <td class="p-4 text-right">
                  <span class="font-semibold" :class="item.days_since_movement > 90 ? 'text-red-600' : item.days_since_movement > 30 ? 'text-amber-600' : 'text-gray-600'">
                    {{ Math.round(item.days_since_movement) }} hari
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 6: EXPIRY / KADALUARSA -->
      <div v-if="activeTab === 'expiry'" class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-gray-800">Peringatan Kadaluarsa</h3>
            <p class="text-xs text-gray-500 mt-0.5">Produk dengan masa berlaku hampir habis</p>
          </div>
          <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-600">
              Dalam:
              <select v-model.number="expiryDays" @change="fetchExpiry" class="input input-sm">
                <option :value="7">7 hari</option>
                <option :value="14">14 hari</option>
                <option :value="30">30 hari</option>
                <option :value="60">60 hari</option>
                <option :value="90">90 hari</option>
              </select>
            </label>
            <ExportButtons :params="{ type: 'expiry', within_days: expiryDays, warehouse_id: selectedWarehouse }" @export="handleExport" />
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">SKU</th>
                <th class="p-4">Produk</th>
                <th class="p-4">Gudang</th>
                <th class="p-4 text-right">Kuantitas</th>
                <th class="p-4 text-right">Tgl Kadaluarsa</th>
                <th class="p-4 text-right">Sisa Hari</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="expiryData.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-500">Tidak ada produk yang akan kadaluarsa dalam periode ini.</td>
              </tr>
              <tr v-for="item in expiryData" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors text-sm">
                <td class="p-4 font-mono text-gray-500">{{ item.product?.sku }}</td>
                <td class="p-4 font-medium text-gray-800">{{ item.product?.name }}</td>
                <td class="p-4 text-gray-600">{{ item.warehouse?.name }}</td>
                <td class="p-4 text-right font-semibold">{{ formatNumber(item.quantity) }}</td>
                <td class="p-4 text-right text-gray-600">{{ formatDate(item.expiry_date) }}</td>
                <td class="p-4 text-right">
                  <span class="font-semibold" :class="daysUntil(item.expiry_date) <= 7 ? 'text-red-600' : daysUntil(item.expiry_date) <= 14 ? 'text-amber-600' : 'text-gray-600'">
                    {{ daysUntil(item.expiry_date) }} hari
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 7: AKTIVITAS -->
      <div v-if="activeTab === 'activity'" class="card">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-bold text-gray-800">Log Aktivitas Stok</h3>
          <ExportButtons :params="{ type: 'activity', warehouse_id: selectedWarehouse }" @export="handleExport" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="p-4">Tanggal</th>
                <th class="p-4">Produk</th>
                <th class="p-4">Gudang</th>
                <th class="p-4">Aktivitas</th>
                <th class="p-4">Oleh</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="activityData.length === 0">
                <td colspan="5" class="p-8 text-center text-gray-500">Belum ada aktivitas.</td>
              </tr>
              <tr v-for="item in activityData" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors text-sm">
                <td class="p-4 text-gray-600">{{ formatDate(item.created_at) }}</td>
                <td class="p-4">
                  <p class="font-medium text-gray-800">{{ item.product?.name }}</p>
                  <p class="text-xs text-gray-500 font-mono">{{ item.product?.sku }}</p>
                </td>
                <td class="p-4 text-gray-600">{{ item.warehouse?.name }}</td>
                <td class="p-4">
                  <span :class="item.type === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" class="px-2 py-1 rounded text-xs font-medium uppercase">
                    {{ item.type }}
                  </span>
                  <span class="text-xs text-gray-400 ml-1">({{ item.reference_type }})</span>
                </td>
                <td class="p-4 text-gray-600">{{ item.user?.name || 'Sistem' }}</td>
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
import ValuationTab from '../components/reports/ValuationTab.vue'
import UtilizationTab from '../components/reports/UtilizationTab.vue'
import ExportButtons from '../components/reports/ExportButtons.vue'
import { reportAPI, warehouseAPI } from '../services/api'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const tabs = [
  { id: 'valuation', label: 'Valuasi Stok' },
  { id: 'utilization', label: 'Utilisasi Gudang' },
  { id: 'mutations', label: 'Mutasi Historis' },
  { id: 'stock', label: 'Stok Lengkap' },
  { id: 'aging', label: 'Aging Stok' },
  { id: 'expiry', label: 'Kadaluarsa' },
  { id: 'activity', label: 'Aktivitas' },
]

const activeTab = ref(localStorage.getItem('_reportTab') || 'valuation')
const loading = ref(false)
const selectedWarehouse = ref('')
const warehouses = ref([])

// Data stores
const valuationData = ref({ total_quantity: 0, total_value: 0, total_products: 0, by_category: [] })
const utilizationData = ref([])
const mutationsData = ref([])
const stockData = ref([])
const agingData = ref([])
const agingDays = ref(30)
const expiryData = ref([])
const expiryDays = ref(30)
const activityData = ref([])

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num || 0)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try { return format(new Date(dateStr), 'dd MMM yyyy HH:mm', { locale: id }) } catch { return dateStr }
}

function daysUntil(dateStr) {
  if (!dateStr) return 999
  const diff = new Date(dateStr) - new Date()
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)))
}

async function fetchWarehouses() {
  try {
    const res = await warehouseAPI.list({ per_page: 100 })
    warehouses.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) {
    console.error(e)
  }
}

function buildParams() {
  return selectedWarehouse.value ? { warehouse_id: selectedWarehouse.value } : {}
}

async function fetchValuation() {
  try {
    const res = await reportAPI.valuation(buildParams())
    valuationData.value = res.data || res
  } catch (e) { console.error(e) }
}

async function fetchUtilization() {
  try {
    const res = await reportAPI.warehouseUtilization(buildParams())
    utilizationData.value = res.data || res
  } catch (e) { console.error(e) }
}

async function fetchMutations() {
  try {
    const res = await reportAPI.movement({ ...buildParams(), per_page: 50 })
    mutationsData.value = res.data || res
  } catch (e) { console.error(e) }
}

async function fetchStock() {
  try {
    const res = await reportAPI.stock({ ...buildParams(), per_page: 200 })
    stockData.value = res.data?.data || []
  } catch (e) { console.error(e) }
}

async function fetchAging() {
  try {
    const res = await reportAPI.aging({ ...buildParams(), days: agingDays.value, per_page: 200 })
    agingData.value = res.data || []
  } catch (e) { console.error(e) }
}

async function fetchExpiry() {
  try {
    const res = await reportAPI.expiry({ ...buildParams(), within_days: expiryDays.value, per_page: 200 })
    expiryData.value = res.data?.data || []
  } catch (e) { console.error(e) }
}

async function fetchActivity() {
  try {
    const res = await reportAPI.activity({ ...buildParams(), per_page: 50 })
    activityData.value = res.data?.data || []
  } catch (e) { console.error(e) }
}

async function handleExport({ type, params }) {
  try {
    const res = await reportAPI.export({ ...params, type })
    const disposition = res.headers?.['content-disposition'] || ''
    const filenameMatch = disposition.match(/filename\*?=(?:UTF-8'')?([^;\s]+)/)
    const ext = params.format === 'pdf' ? '.pdf' : '.xlsx'
    const filename = filenameMatch ? decodeURIComponent(filenameMatch[1]) : `report-${type}${ext}`

    const url = window.URL.createObjectURL(new Blob([res]))
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    // If the API returns JSON (not blob) — fallback to direct download
    const msg = e.response?.data?.message || 'Gagal mengexport laporan'
    alert(msg)
  }
}

async function fetchData() {
  loading.value = true
  try {
    await Promise.all([
      fetchValuation(),
      fetchUtilization(),
      fetchMutations(),
      fetchStock(),
      fetchAging(),
      fetchExpiry(),
      fetchActivity(),
    ])
  } catch (e) {
    console.error('Error fetching reports:', e)
  } finally {
    loading.value = false
  }
}

// Persist active tab across page visits
activeTab.value = localStorage.getItem('_reportTab') || 'valuation'

onMounted(() => {
  fetchWarehouses()
  fetchData()
})
</script>

<style scoped>
</style>
