<template>
  <div class="card p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="font-semibold text-lg">Manajemen Gudang</h3>
      <button class="btn btn-primary">+ Tambah Gudang</button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left text-gray-500">
            <th class="px-4 py-3 font-medium">Kode</th>
            <th class="px-4 py-3 font-medium">Nama</th>
            <th class="px-4 py-3 font-medium">Tipe</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Kapasitas</th>
            <th class="px-4 py-3 font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="wh in warehouses" :key="wh.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-blue-600">{{ wh.code }}</td>
            <td class="px-4 py-3"><router-link :to="`/warehouses/${wh.id}`" class="text-blue-600 hover:underline font-medium">{{ wh.name }}</router-link></td>
            <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ wh.type }}</span></td>
            <td class="px-4 py-3">
              <span :class="wh.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-1 rounded text-xs">
                {{ wh.status }}
              </span>
            </td>
            <td class="px-4 py-3">{{ wh.capacity }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button class="text-blue-600 hover:underline">Edit</button>
                <button class="text-gray-400 hover:text-gray-600">|</button>
                <button @click="openPlanogram(wh)" class="text-blue-600 hover:underline">Planogram</button>
                <button class="text-gray-400 hover:text-gray-600">|</button>
                <button class="text-red-600 hover:underline">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const warehouses = ref([
  { id: 1, code: 'WH001', name: 'Gudang Utama Jakarta', type: 'warehouse', status: 'active', capacity: '7,200 / 10,000' },
  { id: 2, code: 'WH002', name: 'Gudang Distribusi Surabaya', type: 'distribution', status: 'active', capacity: '3,100 / 5,000' },
])

function openPlanogram(wh) {
  router.push(`/planograms/${wh.id}`)
}
</script>