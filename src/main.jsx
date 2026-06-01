import React from 'react'
import { createRoot } from 'react-dom/client'
// HashRouter (URLs like /#/menu) so refreshing any route never 404s on a
// static host, independent of Cloudflare _redirects configuration.
import { HashRouter } from 'react-router-dom'
import App from './App'
import SetupNotice from './components/SetupNotice'
import { AuthProvider } from './context/AuthContext'
import { CartProvider } from './context/CartContext'
import { supabaseConfigured } from './supabaseClient'
import './styles/legacy.css'

const root = createRoot(document.getElementById('root'))

if (!supabaseConfigured) {
  // No DB keys baked in — show a clear message instead of a blank screen.
  root.render(<React.StrictMode><SetupNotice /></React.StrictMode>)
} else {
  root.render(
    <React.StrictMode>
      <HashRouter>
        <AuthProvider>
          <CartProvider>
            <App />
          </CartProvider>
        </AuthProvider>
      </HashRouter>
    </React.StrictMode>
  )
}
