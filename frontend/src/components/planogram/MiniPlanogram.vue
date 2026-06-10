<template>
  <div ref="containerRef" class="w-full h-full" :style="{ minHeight: '120px' }">
    <canvas ref="canvasRef" class="w-full h-full"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'

const props = defineProps({
  canvasData: { type: Object, default: null },
})

const containerRef = ref(null)
const canvasRef = ref(null)

function draw() {
  const canvas = canvasRef.value
  const container = containerRef.value
  if (!canvas || !container || !props.canvasData) return

  const rect = container.getBoundingClientRect()
  canvas.width = rect.width
  canvas.height = rect.height

  const ctx = canvas.getContext('2d')
  const data = props.canvasData

  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Background
  ctx.fillStyle = '#f9fafb'
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  // Auto-scale to fit
  let maxX = 800
  let maxY = 600
  if (data.zones?.length) {
    data.zones.forEach(z => {
      maxX = Math.max(maxX, (z.x || 0) + (z.width || 100))
      maxY = Math.max(maxY, (z.y || 0) + (z.height || 80))
    })
  }
  if (data.items?.length) {
    data.items.forEach(i => {
      maxX = Math.max(maxX, (i.x || 0) + (i.width || 40))
      maxY = Math.max(maxY, (i.y || 0) + (i.height || 40))
    })
  }

  const scaleX = (canvas.width - 20) / maxX
  const scaleY = (canvas.height - 20) / maxY
  const scale = Math.min(scaleX, scaleY, 1)
  const offsetX = 10
  const offsetY = 10

  // Draw zones
  if (data.zones) {
    data.zones.forEach(zone => {
      const x = offsetX + (zone.x || 0) * scale
      const y = offsetY + (zone.y || 0) * scale
      const w = (zone.width || 100) * scale
      const h = (zone.height || 80) * scale

      ctx.fillStyle = zone.color || '#e0e7ff'
      ctx.fillRect(x, y, w, h)
      ctx.strokeStyle = '#c7d2fe'
      ctx.lineWidth = 1
      ctx.strokeRect(x, y, w, h)

      // Zone label
      if (zone.label) {
        ctx.fillStyle = '#6b7280'
        ctx.font = 'bold 10px sans-serif'
        ctx.fillText(zone.label.substring(0, 12), x + 3, y + 14)
      }
    })
  }

  // Draw items
  if (data.items) {
    data.items.forEach(item => {
      const x = offsetX + (item.x || 0) * scale
      const y = offsetY + (item.y || 0) * scale
      const w = Math.max(4, ((item.width || 40) * scale) - 1)
      const h = Math.max(4, ((item.height || 40) * scale) - 1)

      ctx.fillStyle = item.color || '#6366f1'
      // Manual rounded rect (compatible with all browsers)
      const r = Math.min(3, w / 2, h / 2)
      ctx.beginPath()
      ctx.moveTo(x + r, y)
      ctx.lineTo(x + w - r, y)
      ctx.quadraticCurveTo(x + w, y, x + w, y + r)
      ctx.lineTo(x + w, y + h - r)
      ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h)
      ctx.lineTo(x + r, y + h)
      ctx.quadraticCurveTo(x, y + h, x, y + h - r)
      ctx.lineTo(x, y + r)
      ctx.quadraticCurveTo(x, y, x + r, y)
      ctx.closePath()
      ctx.fill()
    })
  }
}

onMounted(() => {
  nextTick(draw)
})

watch(() => props.canvasData, () => {
  nextTick(draw)
}, { deep: true })
</script>
