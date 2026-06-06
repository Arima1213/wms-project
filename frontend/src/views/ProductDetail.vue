<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button @click="router.push('/products')" class="btn btn-sm btn-ghost p-2 -ml-2">
          <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
        </button>
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ product?.name }}</h1>
            <StatusBadge :status="product?.is_active ? 'active' : 'inactive'" :label="product?.is_active ? 'Aktif' : 'Nonaktif'" />
          </div>
          <div class="flex items-center gap-2 mt-0.5 text-sm font-mono text-gray-500">
            <span>{{ product?.code }}</span>
            <span v-if="product?.sku" class="px-1.5 py-0.5 bg-gray-100 rounded text-xs border border-gray-200">SKU: {{ product?.sku }}</span>
          </div>
        </div>
      </div>
      <div class="flex gap-3">
        <button class="btn btn-primary shadow-sm" @click="showEditModal = true">
          <PencilIcon class="w-4 h-4" />
          Edit Produk
        </button>
      </div>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
      <div class="card h-40 bg-white" v-for="i in 3" :key="i"></div>
    </div>

    <!-- Content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Info -->
      <div class="lg:col-span-2 space-y-6">
        <div class="card p-6 border-t-4 border-t-blue-500">
          <h3 class="font-bold text-gray-800 mb-5 pb-4 border-b border-gray-100">Informasi Dasar</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kategori</label>
              <p class="text-sm font-medium text-gray-800 mt-1">{{ product?.category?.name || '-' }}</p>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tipe Produk</label>
              <p class="text-sm font-medium text-gray-800 capitalize mt-1">{{ product?.product_type?.replace('_', ' ') || '-' }}</p>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Barcode</label>
              <p class="text-sm font-mono text-gray-800 mt-1">{{ product?.barcode || '-' }}</p>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Satuan Dasar</label>
              <p class="text-sm font-medium text-gray-800 mt-1">
                {{ product?.unit?.name || '-' }}
                <span v-if="product?.unit?.symbol" class="text-gray-400">({{ product?.unit?.symbol }})</span>
              </p>
            </div>
            <div class="md:col-span-2">
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Deskripsi</label>
              <p class="text-sm text-gray-700 mt-1">{{ product?.description || 'Tidak ada deskripsi.' }}</p>
            </div>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-gray-800 mb-5 pb-4 border-b border-gray-100">Tracking & Dimensi</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center">
              <p class="text-xs text-gray-500 mb-1">Panjang</p>
              <p class="font-semibold text-gray-800">{{ product?.length_cm || 0 }} <span class="text-xs font-normal text-gray-400">cm</span></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center">
              <p class="text-xs text-gray-500 mb-1">Lebar</p>
              <p class="font-semibold text-gray-800">{{ product?.width_cm || 0 }} <span class="text-xs font-normal text-gray-400">cm</span></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center">
              <p class="text-xs text-gray-500 mb-1">Tinggi</p>
              <p class="font-semibold text-gray-800">{{ product?.height_cm || 0 }} <span class="text-xs font-normal text-gray-400">cm</span></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center">
              <p class="text-xs text-gray-500 mb-1">Berat</p>
              <p class="font-semibold text-gray-800">{{ product?.weight_kg || 0 }} <span class="text-xs font-normal text-gray-400">kg</span></p>
            </div>
          </div>
          <div class="flex gap-4">
            <div class="flex items-center gap-2">
              <div :class="product?.track_batch ? 'bg-blue-500' : 'bg-gray-300'" class="w-4 h-4 rounded flex items-center justify-center">
                <CheckIcon v-if="product?.track_batch" class="w-3 h-3 text-white" />
              </div>
              <span class="text-sm text-gray-700">Track Batch/Lot</span>
            </div>
            <div class="flex items-center gap-2">
              <div :class="product?.track_expiry ? 'bg-blue-500' : 'bg-gray-300'" class="w-4 h-4 rounded flex items-center justify-center">
                <CheckIcon v-if="product?.track_expiry" class="w-3 h-3 text-white" />
              </div>
              <span class="text-sm text-gray-700">Track Expiry Date</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <div class="card p-6">
          <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <CubeIcon class="w-5 h-5 text-indigo-500" />
            Ketersediaan Stok
          </h3>
          <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
            <p class="text-4xl font-bold text-gray-800">0</p>
            <p class="text-sm text-gray-500 mt-1">Total Stok Tersedia</p>
            <router-link :to="`/stock?product_id=${product?.id}`" class="btn btn-sm btn-outline mt-4">
              Lihat Rincian Stok
            </router-link>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <MapPinIcon class="w-5 h-5 text-emerald-500" />
            Lokasi Penempatan
          </h3>
          <div class="text-center py-6 text-sm text-gray-500">
            Belum ada data lokasi untuk produk ini.
          </div>
        </div>
      </div>
    </div>

    <Modal v-model="showEditModal" title="Edit Produk">
      <div class="text-center py-4 text-gray-500">
        Fitur edit menggunakan modal utama di Products.vue, ini placeholder untuk detail view.
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../stores/product'
import StatusBadge from '../components/common/StatusBadge.vue'
import Modal from '../components/common/Modal.vue'
import {
  ArrowLeftIcon,
  PencilIcon,
  CheckIcon,
  CubeIcon,
  MapPinIcon
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const store = useProductStore()

const product = computed(() => store.selected)
const loading = computed(() => store.loading)
const showEditModal = ref(false)

async function fetchData() {
  await store.fetchOne(route.params.id)
}

onMounted(() => {
  fetchData()
})
</script>
