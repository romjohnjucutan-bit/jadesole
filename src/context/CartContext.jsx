import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { DISCOUNT_THRESHOLD, DISCOUNT_PERCENT } from '../lib/config'

const CartContext = createContext(null)
const STORAGE_KEY = 'jadesole_cart'

function itemKey(productId, size) {
  return `${productId}::${size || ''}`
}

export function CartProvider({ children }) {
  const [items, setItems] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}
    } catch {
      return {}
    }
  })

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  }, [items])

  function addItem(product, size) {
    const key = itemKey(product.id, size)
    setItems((prev) => {
      const next = { ...prev }
      const existing = next[key]
      if (existing) {
        if (existing.qty < existing.stock) existing.qty += 1
        next[key] = { ...existing }
      } else {
        next[key] = {
          key,
          id: product.id,
          name: product.name,
          price: Number(product.price),
          qty: 1,
          stock: product.stock,
          image: product.image,
          size: size || '',
        }
      }
      return next
    })
  }

  function setQty(key, qty) {
    setItems((prev) => {
      const next = { ...prev }
      const item = next[key]
      if (!item) return prev
      if (qty <= 0) {
        delete next[key]
      } else {
        item.qty = Math.min(qty, item.stock)
        next[key] = { ...item }
      }
      return next
    })
  }

  function removeItem(key) {
    setItems((prev) => {
      const next = { ...prev }
      delete next[key]
      return next
    })
  }

  function clear() {
    setItems({})
  }

  const list = useMemo(() => Object.values(items), [items])
  const subtotal = useMemo(
    () => list.reduce((sum, i) => sum + i.price * i.qty, 0),
    [list]
  )
  const discount = subtotal >= DISCOUNT_THRESHOLD
    ? Math.round(subtotal * DISCOUNT_PERCENT) / 100
    : 0
  const total = subtotal - discount
  const count = list.reduce((sum, i) => sum + i.qty, 0)

  const value = { list, items, addItem, setQty, removeItem, clear, subtotal, discount, total, count }
  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}

export function useCart() {
  const ctx = useContext(CartContext)
  if (!ctx) throw new Error('useCart must be used within CartProvider')
  return ctx
}
