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
              <input type="text" class="input" value="PT Logistik Nusantara Jaya" />
            </div>
            <div>
              <label class="label">Alamat Kantor Pusat</label>
              <textarea class="input" rows="3">Jl. Jendral Sudirman No. 45, Jakarta Selatan</textarea>
            </div>
            <div>
              <label class="label">NPWP</label>
              <input type="text" class="input" value="01.234.567.8-901.000" />
            </div>
            <button class="btn btn-primary mt-4" @click="showSaveToast">Simpan Perusahaan</button>
          </div>
        </div>

        <!-- Tab: Pengguna & RBAC -->
        <div v-if="activeTab === 'users'" class="card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Manajemen Pengguna (RBAC)</h3>
            <button class="btn btn-sm btn-primary">+ Tambah User</button>
          </div>
          <div class="overflow-x-auto">
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
                <tr>
                  <td class="font-medium">Administrator</td>
                  <td class="text-gray-500">admin@wms.local</td>
                  <td><span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-semibold">Super Admin</span></td>
                  <td><span class="text-emerald-600 font-medium">Aktif</span></td>
                  <td><button class="text-blue-600 hover:underline text-sm font-medium">Edit</button></td>
                </tr>
                <tr>
                  <td class="font-medium">Staff Gudang A</td>
                  <td class="text-gray-500">staff@wms.local</td>
                  <td><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">Warehouse Staff</span></td>
                  <td><span class="text-emerald-600 font-medium">Aktif</span></td>
                  <td><button class="text-blue-600 hover:underline text-sm font-medium">Edit</button></td>
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
              <input type="checkbox" class="rounded text-blue-600 w-5 h-5" checked />
              <div>
                <p class="font-medium text-gray-800">Peringatan Stok Menipis (Email)</p>
                <p class="text-xs text-gray-500">Kirim notifikasi email saat stok barang menyentuh ambang batas minimum.</p>
              </div>
            </label>
            <label class="flex items-center gap-3">
              <input type="checkbox" class="rounded text-blue-600 w-5 h-5" checked />
              <div>
                <p class="font-medium text-gray-800">Validasi Barcode Otomatis</p>
                <p class="text-xs text-gray-500">Wajibkan scan barcode pada proses Inbound & Outbound.</p>
              </div>
            </label>
            <div class="pt-4 border-t border-gray-100">
              <label class="label">Zona Waktu (Timezone)</label>
              <select class="input">
                <option>Asia/Jakarta (WIB)</option>
                <option>Asia/Makassar (WITA)</option>
                <option>Asia/Jayapura (WIT)</option>
              </select>
            </div>
            <button class="btn btn-primary mt-4" @click="showSaveToast">Simpan Preferensi</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useNotificationStore } from '../stores/notification'
import BreadCrumb from '../components/common/BreadCrumb.vue'
import {
  UserCircleIcon,
  BuildingOfficeIcon,
  UsersIcon,
  Cog6ToothIcon
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

function showSaveToast() {
  notify.success('Pengaturan berhasil disimpan!')
}
</script>
