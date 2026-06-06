<template>
  <Modal v-model="isOpen" :title="editingZone ? 'Edit Zone' : 'Tambah Zone'" size="md">
    <div class="space-y-4">
      <div>
        <label class="label">Kode Zone <span class="text-red-500">*</span></label>
        <input v-model="form.code" type="text" class="input" placeholder="Z01" />
      </div>
      <div>
        <label class="label">Nama Zone <span class="text-red-500">*</span></label>
        <input v-model="form.name" type="text" class="input" placeholder="Zone A" />
      </div>
      <div>
        <label class="label">Tipe Zone</label>
        <select v-model="form.zone_type" class="input">
          <option value="fast_moving">Fast Moving</option>
          <option value="slow_moving">Slow Moving</option>
          <option value="heavy">Heavy</option>
          <option value="cold">Cold</option>
          <option value="hazmat">Hazmat</option>
        </select>
      </div>
      <div>
        <label class="label">Warna (Preview Planogram)</label>
        <div class="flex gap-2 items-center">
          <input v-model="form.color" type="color" class="h-10 w-16 p-1 border border-gray-200 rounded cursor-pointer" />
          <input v-model="form.color" type="text" class="input flex-1 font-mono uppercase" placeholder="#3B82F6" />
        </div>
      </div>
      <div>
        <label class="label">Deskripsi</label>
        <textarea v-model="form.description" class="input resize-none" rows="2"></textarea>
      </div>
    </div>
    
    <template #footer>
      <div class="flex justify-end gap-3">
        <button @click="isOpen = false" class="btn btn-outline">Batal</button>
        <button @click="save" class="btn btn-primary" :disabled="saving">
          {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '../common/Modal.vue'
import api from '../../services/api'
import { useNotificationStore } from '../../stores/notification'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  warehouseId: { type: [String, Number], required: true },
  editingZone: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue', 'saved'])
const notify = useNotificationStore()

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const saving = ref(false)
const form = ref({
  code: '',
  name: '',
  zone_type: 'fast_moving',
  color: '#3B82F6',
  description: ''
})

watch(() => props.editingZone, (newVal) => {
  if (newVal) {
    form.value = {
      code: newVal.code || '',
      name: newVal.name || '',
      zone_type: newVal.zone_type || 'fast_moving',
      color: newVal.color || '#3B82F6',
      description: newVal.description || ''
    }
  } else {
    form.value = {
      code: '', name: '', zone_type: 'fast_moving', color: '#3B82F6', description: ''
    }
  }
}, { immediate: true })

async function save() {
  if (!form.value.code || !form.value.name) return
  saving.value = true
  
  try {
    if (props.editingZone) {
      await api.put(`/warehouses/${props.warehouseId}/zones/${props.editingZone.id}`, form.value)
      notify.success('Zone berhasil diperbarui')
    } else {
      await api.post(`/warehouses/${props.warehouseId}/zones`, form.value)
      notify.success('Zone berhasil ditambahkan')
    }
    isOpen.value = false
    emit('saved')
  } catch (e) {
    notify.error(e.response?.data?.message || 'Gagal menyimpan zone')
  } finally {
    saving.value = false
  }
}
</script>
