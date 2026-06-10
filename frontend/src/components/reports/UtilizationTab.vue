<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div v-for="wh in data" :key="wh.warehouse.id" class="card p-6 border-t-4 border-t-indigo-500">
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
</template>

<script setup>
defineProps({
  data: { type: Array, required: true },
})
</script>
