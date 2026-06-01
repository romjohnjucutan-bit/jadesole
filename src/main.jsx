import React from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'

// Mount an App to every element with `data-react-root`
document.querySelectorAll('[data-react-root]').forEach((el) => {
  const root = createRoot(el)
  root.render(<App mountId={el.id || null} />)
})
