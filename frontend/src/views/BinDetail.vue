<template>
  <div class="p-6" v-if="bin">
    <div class="flex items-center gap-2 mb-6">
      <router-link to="/bins" class="text-blue-400 hover:text-blue-300 text-sm">← Bin</router-link>
      <span class="text-slate-600">/</span>
      <h1 class="text-2xl font-bold text-white">{{ bin.code }}</h1>
      <span :class="statusClass" class="px-2 py-1 rounded-full text-xs font-medium ml-2">{{ bin.is_active ? 'Aktif' : 'Nonaktif' }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Info Bin</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Tipe:</span> {{ binTypeLabel(bin.bin_type) }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Level:</span> {{ bin.level }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Posisi:</span> {{ bin.position }}</p>
      </div>
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Lokasi</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Rak:</span> {{ bin.rack?.code || '-' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Zona:</span> {{ bin.rack?.zone?.name || '-' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Gudang:</span> {{ bin.rack?.zone?.warehouse?.name || '-' }}</p>
      </div>
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Kapasitas</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Max Berat:</span> {{ bin.max_weight ? bin.max_weight + ' kg' : 'Tak terbatas' }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Max Volume:</span> {{ bin.max_volume ? bin.max_volume + ' m³' : 'Tak terbatas' }}</p>
        <p v-if="occupancy" class="text-sm text-slate-300 mt-2 border-t border-slate-700 pt-2">
          <span class="text-slate-500">Utilisasi Berat:</span> {{ occupancy.weight_utilization_pct }}%
        </p>
      </div>
      <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4">
        <h3 class="text-xs font-medium text-slate-400 uppercase mb-2">Stok</h3>
        <p class="text-sm text-slate-300"><span class="text-slate-500">Total Item:</span> {{ occupancy?.total_items || 0 }}</p>
        <p class="text-sm text-slate-300"><span class="text-slate-500">SKU Unik:</span> {{ occupancy?.sku_count || 0 }}</p>
      </div>
    </div>

    <!-- Stock List -->
    <div class="bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 bg-slate-700/50 border-b border-slate-700">
        <h3 class="text-sm font-medium text-white">Stok di Bin Ini</h3>
      </div>
      <table class="w-full">
        <thead class="bg-slate-700/30">
          <tr>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Produk</th>
            <th class="text-left px-4 py-2 text-xs text-slate-400">SKU</th>
            <th class="text-left px-4 py-2 text-xs text-slate-400">Quantity</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <tr v-for="s in bin.stocks || []" :key="s.id" class="hover:bg-slate-700/30">
            <td class="px-4 py-2 text-sm text-slate-300">{{ s.product?.name || '-' }}</td>
            <td class="px-4 py-2 text-sm text-slate-300">{{ s.product?.sku || '-' }}</td>
            <td class="px-4 py-2 text-sm text-slate-300">{{ s.quantity }}</td>
          </tr>
          <tr v-if="!bin.stocks?.length">
            <td colspan="3" class="px-4 py-4 text-center text-slate-500">Tidak ada stok di bin ini</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Actions -->
    <div class="flex gap-2 mt-6">
      <button @click="handleToggle" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
        {{ bin.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
      </button>
      <button v-if="!bin.stocks?.length" @click="handleDelete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
        Hapus
      </button>
    </div>
  </div>
  <div v-else class="p-6 text-center text-slate-400">Memuat...</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBinStore } from '../stores/bin'

const route = useRoute()
const router = useRouter()
const binStore = useBinStore()

const bin = computed(() => binStore.item)
const occupancy = ref(null)

function binTypeLabel(t) { return { storage: 'Storage', picking: 'Picking', receiving: 'Receiving', shipping: 'Shipping', overflow: 'Overflow', quarantine: 'Quarantine' }[t] || t }
const statusClass = computed(() => bin.value?.is_active ? 'bg-green-600 text-green-100' : 'bg-slate-600 text-slate-200')

async function handleToggle() {
  await binStore.toggleActive(bin.value.id)
  await binStore.fetchBin(route.params.id)
  occupancy.value = await binStore.getOccupancy(route.params.id)
}

async function handleDelete() {
  if (confirm('Hapus bin ini?')) {
    await binStore.deleteBin(bin.value.id)
    router.push('/bins')
  }
}

onMounted(async () => {
  await binStore.fetchBin(route.params.id)
  occupancy.value = await binStore.getOccupancy(route.params.id)
})
</script>
