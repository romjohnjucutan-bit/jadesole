import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// Build a standard SPA into dist/ for Cloudflare Pages.
export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
})
