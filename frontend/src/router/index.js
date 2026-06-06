import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('../layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue')
      },
      {
        path: 'warehouses/:id',
        name: 'WarehouseDetail',
        component: () => import('../views/WarehouseDetail.vue')
      },
      {
        path: 'warehouses',
        name: 'Warehouses',
        component: () => import('../views/Warehouses.vue')
      },
      {
        path: 'products/:id',
        name: 'ProductDetail',
        component: () => import('../views/ProductDetail.vue')
      },
      {
        path: 'products',
        name: 'Products',
        component: () => import('../views/Products.vue')
      },
      {
        path: 'inbounds/:id',
        name: 'InboundDetail',
        component: () => import('../views/InboundDetail.vue')
      },
      {
        path: 'inbounds',
        name: 'Inbounds',
        component: () => import('../views/Inbounds.vue')
      },
      {
        path: 'outbounds/:id',
        name: 'OutboundDetail',
        component: () => import('../views/OutboundDetail.vue')
      },
      {
        path: 'outbounds',
        name: 'Outbounds',
        component: () => import('../views/Outbounds.vue')
      },
      {
        path: 'stock',
        name: 'Stock',
        component: () => import('../views/Stock.vue')
      },
      {
        path: 'stock-opnames',
        name: 'StockOpnames',
        component: () => import('../views/StockOpnames.vue')
      },
      {
        path: 'stock-opnames/:id',
        name: 'StockOpnameDetail',
        component: () => import('../views/StockOpnameDetail.vue')
      },
      {
        path: 'transfers',
        name: 'Transfers',
        component: () => import('../views/Transfers.vue')
      },
      {
        path: 'planograms',
        name: 'Planograms',
        component: () => import('../views/Planograms.vue')
      },
      {
        path: 'planograms/:warehouseId',
        name: 'PlanogramEditor',
        component: () => import('../views/PlanogramEditor.vue')
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('../views/Reports.vue')
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('../views/Settings.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('wms_token')
  if (to.meta.requiresAuth && !token) {
    next({ name: 'Login' })
  } else if (to.meta.guest && token) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router