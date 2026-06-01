import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { supabase } from '../supabaseClient'
import { peso, statusBadgeClass, formatDate } from '../lib/config'

export default function Track() {
  const [params, setParams] = useSearchParams()
  const [orderId, setOrderId] = useState(params.get('id') || '')
  const [order, setOrder] = useState(null)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(null)
  const [loading, setLoading] = useState(false)

  async function lookup(id) {
    if (!id) return
    setLoading(true); setError(null); setSuccess(null); setOrder(null)
    const { data, error } = await supabase.rpc('track_order', { p_order_id: id.trim() })
    setLoading(false)
    if (error) { setError(error.message); return }
    if (!data) { setError('Order not found. Please check your Order ID and try again.'); return }
    setOrder(data)
  }

  // auto-lookup when arriving with ?id=
  useEffect(() => {
    const id = params.get('id')
    if (id) lookup(id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function submit(e) {
    e.preventDefault()
    setParams(orderId ? { id: orderId.trim() } : {})
    lookup(orderId)
  }

  async function cancel() {
    if (!order) return
    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) return
    const { data, error } = await supabase.rpc('cancel_order', { p_order_id: order.order_id })
    if (error) { setError(error.message); return }
    setOrder(data)
    setSuccess('Order has been cancelled successfully.')
  }

  const timeline = order
    ? (order.delivery_option === 'delivery'
        ? ['Received', 'Preparing', 'Out for Delivery', 'Completed']
        : ['Received', 'Preparing', 'Ready for Pickup', 'Completed'])
    : []
  const isCancelled = order?.status === 'Cancelled'
  const fullTimeline = isCancelled ? [...timeline, 'Cancelled'] : timeline
  const currentIdx = fullTimeline.indexOf(order?.status)

  return (
    <section className="section">
      <div className="container">
        <span className="section-tag">Order Tracking</span>
        <h1 className="section-title mb-4">Where's My Order?</h1>

        <div className="track-card mb-4" style={{ maxWidth: 600 }}>
          <form onSubmit={submit}>
            <div className="form-group">
              <label className="form-label">Enter Your Order ID</label>
              <div style={{ display: 'flex', gap: '0.5rem' }}>
                <input className="form-control" placeholder="e.g. JS-XXXXXXXX" value={orderId}
                       onChange={(e) => setOrderId(e.target.value)} style={{ borderRadius: '4px 0 0 4px' }} required />
                <button type="submit" className="btn btn-gold" style={{ borderRadius: '0 4px 4px 0', whiteSpace: 'nowrap' }} disabled={loading}>
                  {loading ? '…' : 'Track →'}
                </button>
              </div>
            </div>
          </form>
          {error && <div className="alert alert-error">{error}</div>}
          {success && <div className="alert alert-success">{success}</div>}
        </div>

        {order && (
          <div className="track-card">
            <div className="track-id-display">
              <div>
                <div className="track-id-label">Order ID</div>
                <div className="track-id-value">{order.order_id}</div>
              </div>
              <span className={`badge ${statusBadgeClass(order.status)}`} style={{ fontSize: '0.8rem', padding: '6px 14px' }}>
                {order.status}
              </span>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '2rem' }}>
              <div><div className="form-label">Customer</div><div>{order.customer_name}</div></div>
              <div><div className="form-label">Contact</div><div>{order.contact_number}</div></div>
              <div><div className="form-label">Option</div><div style={{ textTransform: 'capitalize' }}>{order.delivery_option}</div></div>
              <div><div className="form-label">Payment</div><div style={{ textTransform: 'uppercase' }}>{order.payment_method}</div></div>
              {order.address && (
                <div style={{ gridColumn: '1/-1' }}>
                  <div className="form-label">Delivery Address</div><div>{order.address}</div>
                </div>
              )}
            </div>

            {order.status === 'Received' && (
              <div className="mb-3">
                <button onClick={cancel} className="btn btn-danger" style={{ padding: '8px 16px', borderRadius: 4 }}>Cancel Order</button>
                <span style={{ fontSize: '0.8rem', color: 'var(--text-dim)', marginLeft: '1rem' }}>You can cancel this order since it hasn't been prepared yet.</span>
              </div>
            )}
            {order.status === 'Preparing' && (
              <div className="mb-3">
                <div className="alert alert-info">This order is currently being prepared and cannot be cancelled.</div>
              </div>
            )}

            <div className="data-card mb-3">
              <div className="data-card-header"><h3 className="data-card-title">Items Ordered</h3></div>
              <div className="table-wrap">
                <table>
                  <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                  <tbody>
                    {(order.items || []).map((it) => (
                      <tr key={it.id}>
                        <td>{it.product_name}</td>
                        <td>{it.quantity}</td>
                        <td>{peso(it.price)}</td>
                        <td>{peso(it.subtotal)}</td>
                      </tr>
                    ))}
                    {order.discount > 0 && (
                      <tr><td colSpan="3" style={{ textAlign: 'right', color: 'var(--green)' }}>Discount</td><td style={{ color: 'var(--green)' }}>−{peso(order.discount)}</td></tr>
                    )}
                    <tr>
                      <td colSpan="3" style={{ textAlign: 'right', fontWeight: 700, color: 'var(--white)' }}>Total</td>
                      <td style={{ fontWeight: 700, color: 'var(--gold)' }}>{peso(order.total_amount)}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <h3 style={{ fontSize: '1rem', marginBottom: '1.5rem' }}>Order Progress</h3>
            <div className="order-status-timeline">
              {fullTimeline.map((step, idx) => {
                const done = !isCancelled && idx < currentIdx
                const current = idx === currentIdx
                const cls = done ? 'done' : (current ? 'current' : '')
                return (
                  <div className={`status-step ${cls}`} key={step}>
                    <div className="status-dot" />
                    <div>
                      <div className="status-step-label">{step}</div>
                      {current && !isCancelled && <div style={{ fontSize: '0.78rem', color: 'var(--text-dim)', marginTop: 2 }}>Current status</div>}
                    </div>
                  </div>
                )
              })}
            </div>

            <div className="mt-4 text-dim" style={{ fontSize: '0.8rem' }}>Ordered: {formatDate(order.created_at)}</div>
          </div>
        )}
      </div>
    </section>
  )
}
