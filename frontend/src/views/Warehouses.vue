<template>
  <div class="card p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="font-semibold text-lg">Manajemen Gudang</h3>
      <button @click="showCreateModal = true" class="btn btn-primary">+ Tambah Gudang</button>
    </div>

    <!-- Filter -->
    <div class="flex items-center gap-3 mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Cari gudang..."
        class="input max-w-xs"
        @input="fetchWarehouses"
      />
      <select v-model="filterActive" class="input w-40" @change="fetchWarehouses">
        <option value="">Semua Status</option>
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <!-- Table -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-12 bg-gray-50 rounded animate-pulse"></div>
    </div>

    <div v-else-if="error" class="text-center py-8 text-red-500">
      {{ error }}
      <button @click="fetchWarehouses" class="btn btn-sm btn-outline ml-2">Coba Lagi</button>
    </div>

    <div v-else-if="warehouses.length === 0" class="text-center py-8 text-gray-400">
      Tidak ada gudang
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left text-gray-500 border-b">
            <th class="px-4 py-3 font-medium">Kode</th>
            <th class="px-4 py-3 font-medium">Nama</th>
            <th class="px-4 py-3 font-medium">Tipe</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Kapasitas</th>
            <th class="px-4 py-3 font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="wh in warehouses" :key="wh.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-blue-600">{{ wh.code }}</td>
            <td class="px-4 py-3">
              <router-link :to="`/warehouses/${wh.id}`" class="text-blue-600 hover:underline font-medium">
                {{ wh.name }}
              </router-link>
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs capitalize">
                {{ wh.type || '-' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="wh.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                class="px-2 py-1 rounded text-xs">
                {{ wh.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-4 py-3">
              {{ wh.capacity_sqm ? wh.capacity_sqm + ' m²' : '-' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <router-link :to="`/warehouses/${wh.id}`" class="text-blue-600 hover:underline">Detail</router-link>
                <span class="text-gray-300">|</span>
                <button @click="openPlanogram(wh)" class="text-blue-600 hover:underline">Planogram</button>
                <span class="text-gray-300">|</span>
                <button @click="editWarehouse(wh)" class="text-gray-600 hover:underline">Edit</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
        <p class="text-sm text-gray-400">
          Menampilkan {{ pagination.from }}-{{ pagination.to }} dari {{ pagination.total }}
        </p>
        <div class="flex gap-1">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="fetchWarehouses(page)"
            :class="page === pagination.current_page ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline'"
            class="px-3"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-semibold text-lg mb-4">{{ editingWarehouse ? 'Edit Gudang' : 'Tambah Gudang' }}</h3>
        <div class="space-y-4">
          <div>
            <label class="label">Kode Gudang</label>
            <input v-model="form.code" type="text" class="input" placeholder="WH001" required />
          </div>
          <div>
            <label class="label">Nama Gudang</label>
            <input v-model="form.name" type="text" class="input" placeholder="Gudang Utama" required />
          </div>
          <div>
            <label class="label">Tipe</label>
            <select v-model="form.type" class="input">
              <option value="reguler">Reguler</option>
              <option value="cold_storage">Cold Storage</option>
              <option value="bonded">Bonded</option>
              <option value="konsinyasi">Konsinyasi</option>
            </select>
          </div>
          <div>
            <label class="label">Alamat</label>
            <input v-model="form.address" type="text" class="input" placeholder="Alamat lengkap..." />
          </div>
          <div>
            <label class="label">Kapasitas (m²)</label>
            <input v-model="form.capacity_sqm" type="number" class="input" placeholder="10000" />
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="closeModal" class="btn btn-outline">Batal</button>
          <button @click="saveWarehouse" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { warehouseAPI } from '../services/api'

const router = useRouter()

const warehouses = ref([])
const loading = ref(true)
const error = ref(null)
const searchQuery = ref('')
const filterActive = ref('')
const pagination = ref({ last_page: 1, current_page: 1, from: 0, to: 0, total: 0 })
const showCreateModal = ref(false)
const editingWarehouse = ref(null)
const saving = ref(false)

const form = ref({
  code: '',
  name: '',
  type: 'reguler',
  address: '',
  capacity_sqm: '',
})

async function fetchWarehouses(page = 1) {
  loading.value = true
  error.value = null
  try {
    const params = { page, per_page: 20 }
    if (searchQuery.value) params.search = searchQuery.value
    if (filterActive.value !== '') params.is_active = filterActive.value

    const res = await warehouseAPI.list(params)
    const payload = Array.isArray(res) ? { data: res, last_page: 1, current_page: 1, from: 0, to: 0, total: res.length }
              : Array.isArray(res.data) ? { data: res.data, ...res }
              : res
    warehouses.value = payload.data || payload
    pagination.value = {
      last_page: payload.last_page || 1,
      current_page: payload.current_page || 1,
      from: payload.from || 1,
      to: payload.to || (payload.data || payload).length,
      total: payload.total || (payload.data || payload).length,
    }
  } catch (e) {
    error.value = e.message || 'Gagal memuat gudang'
  } finally {
    loading.value = false
  }
}

function openPlanogram(wh) {
  router.push(`/planograms/${wh.id}`)
}

function editWarehouse(wh) {
  editingWarehouse.value = wh
  form.value = {
    code: wh.code,
    name: wh.name,
    type: wh.type || 'reguler',
    address: wh.address || '',
    capacity_sqm: wh.capacity_sqm || '',
  }
  showCreateModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  editingWarehouse.value = null
  form.value = { code: '', name: '', type: 'reguler', address: '', capacity_sqm: '' }
}

async function saveWarehouse() {
  if (!form.value.code || !form.value.name) return
  saving.value = true
  try {
    const data = { ...form.value }
    if (form.value.capacity_sqm) data.capacity_sqm = Number(form.value.capacity_sqm)

    if (editingWarehouse.value) {
      await warehouseAPI.update(editingWarehouse.value.id, data)
    } else {
      await warehouseAPI.create(data)
    }
    closeModal()
    await fetchWarehouses()
  } catch (e) {
    alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

onMounted(() => fetchWarehouses())
</script>