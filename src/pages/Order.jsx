import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../supabaseClient'
import { peso, categoryEmoji, FREE_DELIVERY_THRESHOLD } from '../lib/config'
import { productImageUrl } from '../lib/images'
import { useCart } from '../context/CartContext'
import SizeModal from '../components/SizeModal'

export default function Order() {
  const [products, setProducts] = useState([])
  const [sizeFor, setSizeFor] = useState(null)
  const [checkout, setCheckout] = useState(false)
  const [success, setSuccess] = useState(null)
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  const { list, addItem, setQty, removeItem, clear, subtotal, discount, total, count } = useCart()

  // checkout form
  const [name, setName] = useState('')
  const [contact, setContact] = useState('')
  const [delivery, setDelivery] = useState('pickup')
  const [address, setAddress] = useState('')
  const [payment, setPayment] = useState('cod')

  useEffect(() => {
    supabase.from('products').select('*, categories(name)')
      .order('category_id').order('name')
      .then(({ data }) => setProducts(data ?? []))
  }, [])

  const grouped = useMemo(() => {
    const out = {}
    for (const p of products) {
      const cat = p.categories?.name || 'Other'
      ;(out[cat] = out[cat] || []).push(p)
    }
    return out
  }, [products])

  async function placeOrder(e) {
    e.preventDefault()
    setError(null)
    if (list.length === 0) return
    setSubmitting(true)
    const items = list.map((i) => ({ product_id: i.id, quantity: i.qty, size: i.size }))
    const { data, error } = await supabase.rpc('place_order', {
      p_customer_name: name,
      p_contact_number: contact,
      p_delivery_option: delivery,
      p_address: delivery === 'delivery' ? address : '',
      p_payment_method: payment,
      p_items: items,
    })
    setSubmitting(false)
    if (error) {
      setError(error.message)
      return
    }
    clear()
    setCheckout(false)
    setSuccess(data)
  }

  return (
    <div className="container section">
      {success && (
        <div className="modal-overlay active">
          <div className="modal">
            <div className="modal-body">
              <div className="order-confirm">
                <div className="order-confirm-icon">✅</div>
                <h2 style={{ fontSize: '1.5rem' }}>Order Placed!</h2>
                <p className="text-dim mt-2">Your order has been received. Use the ID below to track your order.</p>
                <div className="order-confirm-id">{success}</div>
                <p className="text-dim" style={{ fontSize: '0.85rem' }}>Save this Order ID — you'll need it to track your order.</p>
                <div className="flex-center gap-2 mt-4" style={{ flexWrap: 'wrap' }}>
                  <Link to={`/track?id=${encodeURIComponent(success)}`} className="btn btn-gold">Track Order →</Link>
                  <Link to="/" className="btn btn-ghost">Back to Home</Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      <div className="flex-between mb-4 flex-wrap gap-2">
        <div>
          <span className="section-tag">Place an Order</span>
          <h1 className="section-title">Build Your Cart</h1>
        </div>
        <Link to="/menu" className="btn btn-ghost">← Browse Collection</Link>
      </div>

      <div className="order-layout">
        {/* LEFT: products */}
        <div>
          {Object.entries(grouped).map(([catName, items]) => (
            <div className="data-card mb-3" key={catName}>
              <div className="data-card-header">
                <h3 className="data-card-title">{catName}</h3>
              </div>
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
                  </thead>
                  <tbody>
                    {items.map((p) => {
                      const avail = p.is_available && p.stock > 0
                      const img = productImageUrl(p.image)
                      return (
                        <tr key={p.id}>
                          <td>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                              <div style={{ width: 44, height: 44, background: 'var(--dark)', borderRadius: 4, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '1.4rem', flexShrink: 0 }}>
                                {img ? <img src={img} style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: 4 }} /> : categoryEmoji(p.category_id)}
                              </div>
                              <div>
                                <div style={{ fontWeight: 600, fontSize: '0.9rem' }}>{p.name}</div>
                                <div style={{ fontSize: '0.76rem', color: 'var(--text-dim)' }}>{(p.description || '').slice(0, 50)}...</div>
                              </div>
                            </div>
                          </td>
                          <td className="text-gold" style={{ fontWeight: 700 }}>{peso(p.price)}</td>
                          <td>{p.stock}</td>
                          <td>{avail ? <span className="badge badge-green">Available</span> : <span className="badge badge-red">Unavailable</span>}</td>
                          <td>
                            {avail
                              ? <button type="button" className="btn btn-gold btn-sm" onClick={() => setSizeFor(p)}>+ Add</button>
                              : <button className="btn btn-ghost btn-sm" disabled>N/A</button>}
                          </td>
                        </tr>
                      )
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          ))}
        </div>

        {/* RIGHT: cart */}
        <div className="cart-panel">
          <div className="cart-header">
            <h3>My Cart</h3>
            <span className="cart-count">{count} items</span>
          </div>

          {list.length === 0 ? (
            <div className="cart-empty">
              <div className="cart-empty-icon">🛒</div>
              <p>Your cart is empty.</p>
              <p className="text-dim" style={{ fontSize: '0.8rem', marginTop: '0.3rem' }}>Add products from the list.</p>
            </div>
          ) : (
            <>
              <div className="cart-items">
                {list.map((item) => {
                  const img = productImageUrl(item.image)
                  return (
                    <div className="cart-item" key={item.key}>
                      <div className="cart-item-img">
                        {img ? <img src={img} style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: 4 }} /> : '👟'}
                      </div>
                      <div className="cart-item-info">
                        <div className="cart-item-name">{item.name}</div>
                        {item.size && <div className="cart-item-size">Size {item.size}</div>}
                        <div className="cart-item-price">{peso(item.price)} × {item.qty} = {peso(item.price * item.qty)}</div>
                      </div>
                      <div className="cart-qty-ctrl">
                        <button className="qty-btn" onClick={() => setQty(item.key, item.qty - 1)}>−</button>
                        <span className="qty-num">{item.qty}</span>
                        <button className="qty-btn" disabled={item.qty >= item.stock} onClick={() => setQty(item.key, item.qty + 1)}>+</button>
                      </div>
                      <button className="cart-remove" title="Remove" onClick={() => removeItem(item.key)}>✕</button>
                    </div>
                  )
                })}
              </div>

              <div className="cart-summary">
                <div className="cart-row"><span>Subtotal</span><span>{peso(subtotal)}</span></div>
                {discount > 0 && <div className="cart-row discount"><span>Discount (10%)</span><span>−{peso(discount)}</span></div>}
                <div className="cart-row total"><span>Total</span><span>{peso(total)}</span></div>

                {subtotal >= FREE_DELIVERY_THRESHOLD && (
                  <div className="alert alert-success" style={{ marginTop: '0.8rem', fontSize: '0.78rem' }}>🚚 Free delivery eligible!</div>
                )}

                <div className="form-group mt-3">
                  <label className="form-label">Payment Method</label>
                  <div className="payment-methods">
                    <label className={`payment-option${payment === 'cod' ? ' selected' : ''}`} onClick={() => setPayment('cod')}>💵 COD</label>
                    <label className={`payment-option${payment === 'cash' ? ' selected' : ''}`} onClick={() => setPayment('cash')}>💳 Cash</label>
                  </div>
                </div>

                <button className="btn btn-gold w-full mt-2" style={{ justifyContent: 'center' }} onClick={() => setCheckout(true)}>
                  Place Order →
                </button>
              </div>
            </>
          )}
        </div>
      </div>

      {sizeFor && (
        <SizeModal product={sizeFor} onClose={() => setSizeFor(null)} onConfirm={(p, size) => { addItem(p, size); setSizeFor(null) }} />
      )}

      {/* CHECKOUT MODAL */}
      {checkout && (
        <div className="modal-overlay active" onClick={(e) => { if (e.target === e.currentTarget) setCheckout(false) }}>
          <div className="modal">
            <div className="modal-header">
              <h3 className="modal-title">Complete Your Order</h3>
              <button className="modal-close" onClick={() => setCheckout(false)}>✕</button>
            </div>
            <form onSubmit={placeOrder}>
              <div className="modal-body">
                {error && <div className="alert alert-error">{error}</div>}
                <div className="form-group">
                  <label className="form-label">Full Name</label>
                  <input className="form-control" placeholder="e.g. Juan dela Cruz" value={name} onChange={(e) => setName(e.target.value)} required />
                </div>
                <div className="form-group">
                  <label className="form-label">Contact Number</label>
                  <input className="form-control" placeholder="e.g. 09XXXXXXXXX" value={contact} onChange={(e) => setContact(e.target.value)} required />
                </div>
                <div className="form-group">
                  <label className="form-label">Fulfillment Option</label>
                  <select className="form-control" value={delivery} onChange={(e) => setDelivery(e.target.value)}>
                    <option value="pickup">🏪 Store Pickup</option>
                    <option value="delivery">🚚 Delivery</option>
                  </select>
                </div>
                {delivery === 'delivery' && (
                  <div className="form-group">
                    <label className="form-label">Delivery Address</label>
                    <input className="form-control" placeholder="Enter your delivery address" value={address} onChange={(e) => setAddress(e.target.value)} required />
                  </div>
                )}
                <div className="alert alert-info" style={{ fontSize: '0.82rem' }}>
                  💰 <strong>Total: {peso(total)}</strong>{discount > 0 && ` (includes ${peso(discount)} discount)`}
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-ghost" onClick={() => setCheckout(false)}>Cancel</button>
                <button type="submit" className="btn btn-gold" disabled={submitting}>{submitting ? 'Placing…' : 'Confirm Order →'}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
