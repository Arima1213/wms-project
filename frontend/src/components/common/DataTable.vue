<template>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Toolbar -->
    <div v-if="searchable || $slots.toolbar" class="flex items-center justify-between gap-4 px-5 py-3 border-b border-gray-100">
      <div class="flex items-center gap-3 flex-1">
        <!-- Search -->
        <div v-if="searchable" class="relative max-w-xs w-full">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition-all"
          />
        </div>
        <slot name="toolbar" />
      </div>
      <slot name="toolbar-right" />
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50/80 border-b border-gray-100">
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[col.headerClass, col.sortable ? 'cursor-pointer hover:bg-gray-100 select-none' : '']"
              class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider transition-colors"
              @click="col.sortable && toggleSort(col.key)"
            >
              <div class="flex items-center gap-1.5">
                {{ col.label }}
                <template v-if="col.sortable">
                  <ChevronUpIcon v-if="sortKey === col.key && sortDir === 'asc'" class="w-3 h-3 text-blue-500" />
                  <ChevronDownIcon v-else-if="sortKey === col.key && sortDir === 'desc'" class="w-3 h-3 text-blue-500" />
                  <ChevronUpDownIcon v-else class="w-3 h-3 text-gray-300" />
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading -->
          <tr v-if="loading">
            <td :colspan="columns.length" class="px-5 py-16 text-center text-gray-400">
              <div class="flex flex-col items-center gap-2">
                <ArrowPathIcon class="w-6 h-6 animate-spin" />
                <span class="text-sm">Memuat data...</span>
              </div>
            </td>
          </tr>

          <!-- Empty -->
          <tr v-else-if="!filteredData.length">
            <td :colspan="columns.length" class="px-5 py-12">
              <EmptyState
                :title="emptyTitle"
                :description="emptyDescription"
              />
            </td>
          </tr>

          <!-- Rows -->
          <tr
            v-else
            v-for="(row, rowIdx) in filteredData"
            :key="rowKey ? row[rowKey] : rowIdx"
            @click="$emit('row-click', row)"
            :class="[rowClickable ? 'cursor-pointer hover:bg-blue-50/50' : 'hover:bg-gray-50/50']"
            class="border-b border-gray-50 last:border-0 transition-colors"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              :class="col.cellClass"
              class="px-5 py-3.5"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="getCellValue(row, col.key)">
                {{ getCellValue(row, col.key) }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="paginated && pagination" class="flex items-center justify-between px-5 py-3 border-t border-gray-100">
      <Pagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :from="pagination.from"
        :to="pagination.to"
        @page-change="$emit('page-change', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  MagnifyingGlassIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronUpDownIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import Pagination from './Pagination.vue'
import EmptyState from './EmptyState.vue'

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    // [{ key: 'name', label: 'Name', sortable: true, headerClass: '', cellClass: '' }]
  },
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  searchable: { type: Boolean, default: false },
  searchPlaceholder: { type: String, default: 'Cari...' },
  searchKeys: { type: Array, default: () => [] },
  paginated: { type: Boolean, default: false },
  pagination: { type: Object, default: null },
  rowKey: { type: String, default: 'id' },
  rowClickable: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'Tidak ada data' },
  emptyDescription: { type: String, default: 'Data belum tersedia.' },
})

defineEmits(['row-click', 'page-change'])

const searchQuery = ref('')
const sortKey = ref(null)
const sortDir = ref('asc')

function getCellValue(row, key) {
  return key.split('.').reduce((acc, part) => acc?.[part], row)
}

function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const filteredData = computed(() => {
  let result = [...props.data]

  // Search
  if (searchQuery.value && props.searchKeys.length) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(row =>
      props.searchKeys.some(key => {
        const val = getCellValue(row, key)
        return val && String(val).toLowerCase().includes(q)
      })
    )
  }

  // Sort
  if (sortKey.value) {
    result.sort((a, b) => {
      const aVal = getCellValue(a, sortKey.value)
      const bVal = getCellValue(b, sortKey.value)
      if (aVal == null) return 1
      if (bVal == null) return -1
      const cmp = String(aVal).localeCompare(String(bVal), undefined, { numeric: true })
      return sortDir.value === 'asc' ? cmp : -cmp
    })
  }

  return result
})
</script>
