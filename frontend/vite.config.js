import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

// Docker: use service name (port 80 internal). Local dev: fallback to localhost
const BACKEND_HOST = process.env.VITE_BACKEND_HOST || 'localhost'
const BACKEND_PORT = process.env.VITE_BACKEND_PORT || (BACKEND_HOST === 'localhost' ? '8000' : '80')

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: `http://${BACKEND_HOST}:${BACKEND_PORT}`,
        changeOrigin: true,
      },
      '/sanctum': {
        target: `http://${BACKEND_HOST}:${BACKEND_PORT}`,
        changeOrigin: true,
      },
    },
  },
})
