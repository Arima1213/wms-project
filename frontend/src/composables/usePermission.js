import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export function usePermission() {
  const auth = useAuthStore()

  const can = (permission) => auth.hasPermission(permission)
  const hasRole = (role) => auth.hasRole(role)
  const isAdmin = computed(() => auth.hasRole('super_admin'))
  const isManager = computed(() => auth.hasRole('manager') || isAdmin.value)

  return { can, hasRole, isAdmin, isManager }
}
