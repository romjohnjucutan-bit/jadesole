import { useState } from 'react'

// Sizes 5 → 13 in 0.5 steps (matches legacy order.php).
const SIZES = []
for (let s = 5; s <= 13; s += 0.5) SIZES.push(s)

export default function SizeModal({ product, onClose, onConfirm }) {
  const [size, setSize] = useState('')

  function submit(e) {
    e.preventDefault()
    if (!size) return
    onConfirm(product, size)
  }

  return (
    <div className="modal-overlay active" onClick={(e) => { if (e.target === e.currentTarget) onClose() }}>
      <div className="modal">
        <div className="modal-header">
          <h3 className="modal-title">Select Shoe Size</h3>
          <button className="modal-close" onClick={onClose}>✕</button>
        </div>
        <form onSubmit={submit}>
          <div className="modal-body">
            <div className="form-group">
              <label className="form-label">Size — {product.name}</label>
              <select className="form-control" value={size} onChange={(e) => setSize(e.target.value)} required>
                <option value="">-- Choose size --</option>
                {SIZES.map((s) => <option key={s} value={s}>{s}</option>)}
              </select>
            </div>
          </div>
          <div className="modal-footer">
            <button type="button" className="btn btn-ghost" onClick={onClose}>Cancel</button>
            <button type="submit" className="btn btn-gold">Add to Cart</button>
          </div>
        </form>
      </div>
    </div>
  )
}
