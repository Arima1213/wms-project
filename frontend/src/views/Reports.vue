<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Pusat Laporan</h2>
        <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Laporan'}]" class="mt-1" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="report in reports" :key="report.id" class="card p-6 border border-gray-100 hover:border-blue-200 transition-colors group cursor-pointer" @click="openReport(report)">
        <div :class="report.color" class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
          <component :is="report.icon" class="w-6 h-6" />
        </div>
        <h3 class="font-bold text-gray-800 mb-2">{{ report.title }}</h3>
        <p class="text-sm text-gray-500 mb-4">{{ report.desc }}</p>
        <button class="text-sm font-medium text-blue-600 group-hover:underline flex items-center gap-1">
          Akses Laporan <ArrowRightIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <Modal v-model="showReportModal" :title="selectedReport?.title" size="lg">
      <div class="text-center py-12">
        <DocumentChartBarIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-800 mb-2">Modul {{ selectedReport?.title }} sedang disiapkan</h3>
        <p class="text-sm text-gray-500 max-w-md mx-auto">
          Fitur reporting lanjutan seperti ekspor PDF/Excel dan filter data historis akan tersedia pada pembaruan rilis berikutnya.
        </p>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button @click="showReportModal = false" class="btn btn-primary">Tutup</button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import Modal from '../components/common/Modal.vue'
import {
  DocumentChartBarIcon,
  ArrowRightIcon,
  ChartBarIcon,
  ClockIcon,
  ClipboardDocumentCheckIcon,
  CurrencyDollarIcon,
  TruckIcon
} from '@heroicons/vue/24/outline'

const reports = ref([
  { id: 'stock_valuation', title: 'Valuasi Stok', desc: 'Laporan nilai finansial persediaan berdasarkan metode HPP.', icon: CurrencyDollarIcon, color: 'bg-emerald-100 text-emerald-600' },
  { id: 'stock_movement', title: 'Mutasi Stok Historis', desc: 'Jejak pergerakan barang masuk, keluar, dan transfer internal.', icon: ClockIcon, color: 'bg-blue-100 text-blue-600' },
  { id: 'warehouse_util', title: 'Utilisasi Gudang', desc: 'Analisis tingkat keterisian dan produktivitas area penyimpanan.', icon: ChartBarIcon, color: 'bg-indigo-100 text-indigo-600' },
  { id: 'inbound_perf', title: 'Kinerja Inbound', desc: 'Metrik waktu bongkar muat dan ketepatan penerimaan PO.', icon: ClipboardDocumentCheckIcon, color: 'bg-orange-100 text-orange-600' },
  { id: 'outbound_perf', title: 'Kinerja Outbound', desc: 'Waktu rata-rata picking, packing, dan pemenuhan Sales Order.', icon: TruckIcon, color: 'bg-rose-100 text-rose-600' },
])

const showReportModal = ref(false)
const selectedReport = ref(null)

function openReport(report) {
  selectedReport.value = report
  showReportModal.value = true
}
</script>
