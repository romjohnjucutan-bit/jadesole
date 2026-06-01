import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../supabaseClient'
import { peso, statusBadgeClass, formatDate, ORDER_STATUSES } from '../../lib/config'

export default function AdminOrders() {
  const [orders, setOrders] = useState([])
  const [filter, setFilter] = useState('all')

  const load = useCallback(async () => {
    let q = supabase.from('orders').select('*, order_items(quantity)').order('created_at', { ascending: false })
    if (filter !== 'all') q = q.eq('status', filter)
    const { data } = await q
    setOrders(data ?? [])
  }, [filter])

  useEffect(() => { load() }, [load])

  async function updateStatus(orderId, status) {
    await supabase.from('orders').update({ status }).eq('order_id', orderId)
    load()
  }

  return (
    <>
      <div className="dash-header"><h1 className="dash-title">All Orders</h1></div>

      <div className="products-filter mb-3">
        <button className={`filter-btn${filter === 'all' ? ' active' : ''}`} onClick={() => setFilter('all')}>All</button>
        {ORDER_STATUSES.map((s) => (
          <button key={s} className={`filter-btn${filter === s ? ' active' : ''}`} onClick={() => setFilter(s)}>{s}</button>
        ))}
      </div>

      <div className="data-card">
        <div className="table-wrap">
          <table>
            <thead>
              <tr><th>Order ID</th><th>Customer</th><th>Contact</th><th>Items</th><th>Total</th><th>Option</th><th>Payment</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
              {orders.map((o) => {
                const itemCount = (o.order_items || []).reduce((s, i) => s + i.quantity, 0)
                return (
                  <tr key={o.id}>
                    <td><span className="text-gold" style={{ fontFamily: 'monospace', fontSize: '0.82rem' }}>{o.order_id}</span></td>
                    <td>{o.customer_name}</td>
                    <td style={{ fontSize: '0.82rem' }}>{o.contact_number}</td>
                    <td>{itemCount} pcs</td>
                    <td className="text-gold">{peso(o.total_amount)}</td>
                    <td style={{ textTransform: 'capitalize', fontSize: '0.82rem' }}>{o.delivery_option}</td>
                    <td style={{ textTransform: 'uppercase', fontSize: '0.8rem' }}>{o.payment_method}</td>
                    <td>
                      <select
                        className="form-control"
                        value={o.status}
                        onChange={(e) => updateStatus(o.order_id, e.target.value)}
                        style={{ padding: '6px 8px', fontSize: '0.78rem', width: 'auto' }}
                      >
                        {ORDER_STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
                      </select>
                      <span className={`badge ${statusBadgeClass(o.status)}`} style={{ marginLeft: 6 }}>{o.status}</span>
                    </td>
                    <td style={{ fontSize: '0.8rem', color: 'var(--text-dim)' }}>{formatDate(o.created_at)}</td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>
    </>
  )
}
