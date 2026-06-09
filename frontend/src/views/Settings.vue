<template>
  <div class="space-y-6 max-w-5xl mx-auto">
    <div>
      <h2 class="text-2xl font-bold text-gray-800">Pengaturan Sistem</h2>
      <BreadCrumb :crumbs="[{label: 'Dashboard', to: '/'}, {label: 'Pengaturan'}]" class="mt-1" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar Settings Menu -->
      <div class="md:col-span-1 space-y-2">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          class="w-full text-left px-4 py-3 rounded-lg text-sm font-medium transition-colors flex items-center gap-3"
          :class="activeTab === tab.id ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'">
          <component :is="tab.icon" class="w-5 h-5" :class="activeTab === tab.id ? 'text-blue-600' : 'text-gray-400'" />
          {{ tab.name }}
        </button>
      </div>

      <!-- Settings Content -->
      <div class="md:col-span-3">
        <!-- Tab: Profil -->
        <div v-if="activeTab === 'profile'" class="card p-6">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Profil Pengguna</h3>
          <div class="flex items-center gap-6 mb-8">
            <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl font-bold">
              {{ auth.user?.name?.charAt(0) || 'A' }}
            </div>
            <div>
              <button class="btn btn-sm btn-outline">Ubah Foto</button>
              <p class="text-xs text-gray-500 mt-2">Format JPG, PNG max 2MB.</p>
            </div>
          </div>
          <div class="space-y-4 max-w-md">
            <div>
              <label class="label">Nama Lengkap</label>
              <input type="text" class="input" :value="auth.user?.name" disabled />
            </div>
            <div>
              <label class="label">Email</label>
              <input type="email" class="input" :value="auth.user?.email" disabled />
            </div>
            <div>
              <label class="label">Role (Hak Akses)</label>
              <input type="text" class="input capitalize" :value="auth.user?.role || 'Admin'" disabled />
            </div>
            <button class="btn btn-primary mt-4" disabled>Simpan Perubahan</button>
          </div>
        </div>

        <!-- Tab: Perusahaan -->
        <div v-if="activeTab === 'company'" class="card p-6">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Informasi Perusahaan</h3>
          <div class="space-y-4 max-w-md">
            <div>
              <label class="label">Nama Perusahaan</label>
              <input type="text" v-model="settings.company_name" class="input" placeholder="PT Logistik Nusantara Jaya" />
            </div>
            <div>
              <label class="label">Alamat Kantor Pusat</label>
              <textarea v-model="settings.company_address" class="input" rows="3" placeholder="Alamat lengkap..."></textarea>
            </div>
            <div>
              <label class="label">NPWP</label>
              <input type="text" v-model="settings.company_npwp" class="input" placeholder="01.234.567.8-901.000" />
            </div>
            <button class="btn btn-primary mt-4" @click="saveSettings" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan Perusahaan' }}
            </button>
          </div>
        </div>

        <!-- Tab: Pengguna & RBAC -->
        <div v-if="activeTab === 'users'" class="card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Manajemen Pengguna (RBAC)</h3>
            <button @click="openCreateModal" class="btn btn-sm btn-primary" :disabled="loading">
              + Tambah User
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="loading" class="py-12 flex justify-center">
            <ArrowPathIcon class="w-8 h-8 animate-spin text-blue-600" />
          </div>

          <!-- Empty state -->
          <div v-else-if="users.length === 0" class="py-12 text-center text-gray-400">
            Belum ada pengguna. Klik "+ Tambah User" untuk menambahkan.
          </div>

          <!-- Users table -->
          <div v-else class="overflow-x-auto">
            <table class="table w-full">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id">
                  <td class="font-medium">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                        :class="getAvatarClass(user)">
                        {{ user.name?.charAt(0) || '?' }}
                      </div>
                      {{ user.name }}
                    </div>
                  </td>
                  <td class="text-gray-500">{{ user.email }}</td>
                  <td>
                    <span v-for="role in user.roles" :key="role.id"
                      class="inline-block px-2 py-1 rounded text-xs font-semibold mr-1"
                      :class="getRoleBadgeClass(role.name)">
                      {{ role.name }}
                    </span>
                    <span v-if="!user.roles?.length" class="text-gray-400 text-xs">—</span>
                  </td>
                  <td>
                    <span class="font-medium text-sm"
                      :class="user.is_active ? 'text-emerald-600' : 'text-red-400'">
                      {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td>
                    <div class="flex items-center gap-2">
                      <button @click="openEditModal(user)" class="text-blue-600 hover:underline text-sm font-medium">
                        Edit
                      </button>
                      <button v-if="canDelete(user)" @click="deleteUser(user)"
                        class="text-red-500 hover:underline text-sm font-medium">
                        Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab: Preferensi -->
        <div v-if="activeTab === 'preferences'" class="card p-6">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Preferensi Sistem</h3>
          <div class="space-y-6 max-w-md">
            <label class="flex items-center gap-3">
              <input type="checkbox" v-model="settings.pref_low_stock_email" true-value="1" false-value="0" class="rounded text-blue-600 w-5 h-5" />
              <div>
                <p class="font-medium text-gray-800">Peringatan Stok Menipis (Email)</p>
                <p class="text-xs text-gray-500">Kirim notifikasi email saat stok barang menyentuh ambang batas minimum.</p>
              </div>
            </label>
            <label class="flex items-center gap-3">
              <input type="checkbox" v-model="settings.pref_barcode_validation" true-value="1" false-value="0" class="rounded text-blue-600 w-5 h-5" />
              <div>
                <p class="font-medium text-gray-800">Validasi Barcode Otomatis</p>
                <p class="text-xs text-gray-500">Wajibkan scan barcode pada proses Inbound & Outbound.</p>
              </div>
            </label>
            <div class="pt-4 border-t border-gray-100">
              <label class="label">Zona Waktu (Timezone)</label>
              <select v-model="settings.timezone" class="input">
                <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
              </select>
            </div>
            <button class="btn btn-primary mt-4" @click="saveSettings" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan Preferensi' }}
            </button>
          </div>
        </div>

        <!-- Modal: Create/Edit User -->
        <Modal v-model="showUserModal" :title="isEditing ? 'Edit User' : 'Tambah User Baru'" size="md">
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2 sm:col-span-1">
                <label class="label">Nama Lengkap <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" class="input" placeholder="Nama pengguna" />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="label">Email <span class="text-red-500">*</span></label>
                <input v-model="form.email" type="email" class="input" placeholder="email@example.com" />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="label">{{ isEditing ? 'Password Baru (biarkan kosong jika tidak diubah)' : 'Password' }} <span class="text-red-500">*</span></label>
                <input v-model="form.password" type="password" class="input" placeholder="Min 8 karakter" />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="label">{{ isEditing ? 'Konfirmasi Password Baru' : 'Konfirmasi Password' }}</label>
                <input v-model="form.password_confirmation" type="password" class="input" placeholder="Ulangi password" />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="label">Role</label>
                <select v-model="form.roles" class="input">
                  <option value="">-- Pilih Role --</option>
                  <option v-for="role in availableRoles" :key="role.id" :value="role.name">{{ role.name }}</option>
                </select>
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="label">Status</label>
                <select v-model="form.is_active" class="input">
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="label">No. Telepon</label>
                <input v-model="form.phone" type="text" class="input" placeholder="08xxxxxxxxxx" />
              </div>
            </div>
          </div>
          <template #footer>
            <div class="flex justify-end gap-3">
              <button @click="showUserModal = false" class="btn btn-outline">Batal</button>
              <button @click="saveUser" class="btn btn-primary" :disabled="submitting">
                {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah User') }}
              </button>
            </div>
          </template>
        </Modal>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useNotificationStore } from '../stores/notification'
import { settingAPI, userAPI } from '../services/api'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import Modal from '../components/common/Modal.vue'
import {
  UserCircleIcon,
  BuildingOfficeIcon,
  UsersIcon,
  Cog6ToothIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const auth = useAuthStore()
const notify = useNotificationStore()

const tabs = [
  { id: 'profile', name: 'Profil Saya', icon: UserCircleIcon },
  { id: 'company', name: 'Profil Perusahaan', icon: BuildingOfficeIcon },
  { id: 'users', name: 'Pengguna & Hak Akses', icon: UsersIcon },
  { id: 'preferences', name: 'Preferensi Sistem', icon: Cog6ToothIcon },
]

const activeTab = ref('profile')
const saving = ref(false)

const settings = ref({
  company_name: 'PT Logistik Nusantara Jaya',
  company_address: 'Jl. Jendral Sudirman No. 45, Jakarta Selatan',
  company_npwp: '01.234.567.8-901.000',
  pref_low_stock_email: '1',
  pref_barcode_validation: '1',
  timezone: 'Asia/Jakarta'
})

// ── User Management ──
const users = ref([])
const availableRoles = ref([])
const loading = ref(false)
const submitting = ref(false)
const showUserModal = ref(false)
const isEditing = ref(false)

const editingUserId = ref(null)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: '',
  is_active: true,
  phone: '',
})

function resetForm() {
  form.name = ''
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  form.roles = ''
  form.is_active = true
  form.phone = ''
}

async function fetchUsers() {
  loading.value = true
  try {
    const res = await userAPI.list({ per_page: 50 })
    users.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) {
    notify.error('Gagal memuat data pengguna')
  } finally {
    loading.value = false
  }
}

async function fetchRoles() {
  try {
    const res = await userAPI.roles()
    availableRoles.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) {
    // roles not critical
  }
}

function getRoleBadgeClass(roleName) {
  const map = {
    'Super Admin': 'bg-purple-100 text-purple-700',
    'admin': 'bg-purple-100 text-purple-700',
    'Warehouse Staff': 'bg-blue-100 text-blue-700',
    'staff': 'bg-blue-100 text-blue-700',
    'Manager': 'bg-amber-100 text-amber-700',
    'manager': 'bg-amber-100 text-amber-700',
    'Supervisor': 'bg-teal-100 text-teal-700',
    'supervisor': 'bg-teal-100 text-teal-700',
  }
  return map[roleName] || 'bg-gray-100 text-gray-700'
}

function getAvatarClass(user) {
  const colors = [
    'bg-blue-100 text-blue-600',
    'bg-green-100 text-green-600',
    'bg-purple-100 text-purple-600',
    'bg-amber-100 text-amber-600',
    'bg-pink-100 text-pink-600',
    'bg-teal-100 text-teal-600',
  ]
  return colors[(user.id || 0) % colors.length]
}

function canDelete(user) {
  return user.id !== auth.user?.id
}

function openCreateModal() {
  isEditing.value = false
  resetForm()
  showUserModal.value = true
}

function openEditModal(user) {
  isEditing.value = true
  editingUserId.value = user.id
  form.name = user.name || ''
  form.email = user.email || ''
  form.password = ''
  form.password_confirmation = ''
  form.roles = user.roles?.[0]?.name || ''
  form.is_active = user.is_active ?? true
  form.phone = user.phone || ''
  showUserModal.value = true
}

async function saveUser() {
  if (!form.name || !form.email) {
    notify.error('Nama dan Email harus diisi')
    return
  }
  if (!isEditing.value && !form.password) {
    notify.error('Password harus diisi untuk user baru')
    return
  }
  if (form.password && form.password.length < 8) {
    notify.error('Password minimal 8 karakter')
    return
  }
  if (form.password !== form.password_confirmation) {
    notify.error('Konfirmasi password tidak cocok')
    return
  }

  submitting.value = true
  try {
    const payload = {
      name: form.name,
      email: form.email,
      is_active: form.is_active,
      phone: form.phone || undefined,
    }
    if (form.password) {
      payload.password = form.password
    }
    if (form.roles) {
      payload.roles = [form.roles]
    }

    if (isEditing.value) {
      await userAPI.update(editingUserId.value, payload)
    } else {
      await userAPI.create(payload)
    }
    showUserModal.value = false
    await fetchUsers()
  } catch (e) {
    // Error handled by api interceptor
  } finally {
    submitting.value = false
  }
}

async function deleteUser(user) {
  if (!confirm(`Yakin ingin menghapus user "${user.name}"?`)) return
  try {
    await userAPI.delete(user.id)
    await fetchUsers()
  } catch (e) {
    // handled by api interceptor
  }
}

async function fetchSettings() {
  try {
    const res = await settingAPI.index()
    const data = res.data?.data || res.data || {}
    for (const key in data) {
      if (settings.value.hasOwnProperty(key)) {
        settings.value[key] = data[key]
      }
    }
  } catch (e) {
    console.error('Failed to load settings', e)
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await settingAPI.update(settings.value)
    notify.success('Pengaturan berhasil disimpan!')
  } catch (e) {
    notify.error('Gagal menyimpan pengaturan')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
  fetchUsers()
  fetchRoles()
})
</script>
