<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card p-6 border-l-4 border-l-emerald-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Nilai Finansial</p>
        <p class="text-3xl font-bold text-gray-800">Rp {{ formatNumber(data.total_value) }}</p>
      </div>
      <div class="card p-6 border-l-4 border-l-blue-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Kuantitas Fisik</p>
        <p class="text-3xl font-bold text-gray-800">{{ formatNumber(data.total_quantity) }}</p>
      </div>
      <div class="card p-6 border-l-4 border-l-purple-500">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">SKU Unik Tersedia</p>
        <p class="text-3xl font-bold text-gray-800">{{ formatNumber(data.total_products) }}</p>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="font-bold text-gray-800 mb-5">Distribusi Nilai Berdasarkan Kategori</h3>
      <div v-if="data.by_category?.length === 0" class="text-center py-6 text-gray-500">Belum ada data.</div>
      <div class="space-y-5">
        <div v-for="(cat, idx) in data.by_category" :key="idx" class="relative">
          <div class="flex justify-between text-sm mb-1.5">
            <span class="font-medium text-gray-700">{{ cat.category }} ({{ formatNumber(cat.quantity) }} unit)</span>
            <span class="font-bold text-gray-800">Rp {{ formatNumber(cat.value) }}</span>
          </div>
          <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="bg-blue-500 h-full rounded-full transition-all duration-1000" :style="{ width: (cat.value / data.total_value * 100) + '%' }"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  data: { type: Object, required: true },
  formatNumber: { type: Function, required: true },
})
</script>
