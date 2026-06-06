<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-slate-800">WMS</h1>
        <p class="text-slate-500 mt-2">Multi-Gudang Management System</p>
      </div>

      <div class="card p-8">
        <h2 class="text-xl font-semibold mb-6">Login</h2>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="admin@wms.local" required />
          </div>
          <div>
            <label class="label">Password</label>
            <input v-model="form.password" type="password" class="input" placeholder="••••••••" required />
          </div>

          <div v-if="error" class="p-3 bg-red-50 text-red-600 rounded-lg text-sm">
            {{ error }}
          </div>

          <button type="submit" :disabled="loading" class="btn btn-primary w-full">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
          Demo: admin@wms.local / password123
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '../services/api'

const router = useRouter()
const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await authAPI.login(form.value)
    localStorage.setItem('wms_token', res.data.token)
    localStorage.setItem('wms_user', JSON.stringify(res.data.user))
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed. Check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>