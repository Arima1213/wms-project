import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)
  const loading = ref(false)

  const isLoggedIn = computed(() => !!token.value)
  const userInitials = computed(() => {
    if (!user.value?.name) return 'U'
    return user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  })
  const permissions = computed(() => user.value?.permissions || [])

  function hasPermission(permission) {
    if (!user.value) return false
    if (user.value.roles?.includes('super_admin')) return true
    return permissions.value.includes(permission)
  }

  function hasRole(role) {
    return user.value?.roles?.includes(role) || false
  }

  async function login(credentials) {
    loading.value = true
    try {
      // 1. Get CSRF cookie first (Sanctum SPA requirement)
      await api.get('/sanctum/csrf-cookie')
      // 2. Login — server sets httpOnly session cookie
      const res = await api.post('/login', credentials)
      user.value = res.data?.user || res.user
      token.value = 'authenticated' // dummy value — real auth is in httpOnly cookie
      return { success: true }
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Login gagal' }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {}
    token.value = null
    user.value = null
  }

  async function fetchProfile() {
    try {
      const res = await api.get('/me')
      user.value = res.data || res
    } catch {}
  }

  return {
    user, token, loading,
    isLoggedIn, userInitials, permissions,
    hasPermission, hasRole,
    login, logout, fetchProfile,
  }
})
