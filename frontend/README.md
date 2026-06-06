# WMS Multi-Gudang Frontend

Built with Vue 3 + Vite + Pinia + Konva.js

## Tech Stack

- **Vue 3** - Composition API
- **Vite** - Build tool
- **Pinia** - State management
- **Vue Router** - Routing
- **Konva.js + vue-konva** - Planogram canvas 2.5D
- **Axios** - HTTP client
- **TailwindCSS** - Styling

## Project Structure

```
frontend/
├── public/
├── src/
│   ├── assets/
│   ├── components/
│   │   ├── common/
│   │   ├── planogram/
│   │   └── warehouse/
│   ├── composables/
│   ├── layouts/
│   ├── router/
│   ├── services/
│   ├── stores/
│   ├── views/
│   └── App.vue
├── index.html
├── vite.config.js
├── tailwind.config.js
└── package.json
```

## Setup

```bash
npm install
npm run dev
```

## Docker

Frontend runs in container, accessible at http://localhost:3000