import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { getNotifications } from '../../lib/notifications'
import { peso, timeAgo } from '../../lib/config'

export default function AdminNotifications() {
  const { isAdmin } = useAuth()
  const [items, setItems] = useState([])

  useEffect(() => {
    getNotifications(isAdmin).then(({ items }) => setItems(items))
  }, [isAdmin])

  const orders = items.filter((n) => n.type === 'order')
  const stocks = items.filter((n) => n.type === 'stock')

  return (
    <>
      <div className="dash-header">
        <h1 className="dash-title">🔔 Notifications</h1>
        <span className="text-dim" style={{ fontSize: '0.85rem' }}>{items.length} alert{items.length !== 1 ? 's' : ''}</span>
      </div>

      {items.length === 0 ? (
        <div className="data-card" style={{ textAlign: 'center', padding: '4rem 2rem' }}>
          <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🔔</div>
          <h3 style={{ color: 'var(--white)', marginBottom: '0.5rem' }}>All caught up!</h3>
          <p className="text-dim">No new orders or low stock alerts at the moment.</p>
        </div>
      ) : (
        <>
          {orders.length > 0 && (
            <div className="data-card" style={{ marginBottom: '1.5rem' }}>
              <div className="data-card-header">
                <h3 className="data-card-title">📦 New Orders <span className="badge badge-blue" style={{ marginLeft: 8 }}>{orders.length}</span></h3>
                <Link to="/admin/orders" className="btn btn-ghost btn-sm">Manage Orders</Link>
              </div>
              <div className="table-wrap">
                <table>
                  <thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Time</th></tr></thead>
                  <tbody>
                    {orders.map((n, i) => (
                      <tr key={i}>
                        <td><Link to="/admin/orders" style={{ color: 'var(--coral)', fontFamily: 'monospace', fontSize: '0.85rem' }}>{n.orderId}</Link></td>
                        <td>{n.customer}</td>
                        <td className="text-gold">{peso(n.amount)}</td>
                        <td style={{ fontSize: '0.8rem', color: 'var(--text-dim)' }}>{timeAgo(n.time)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {stocks.length > 0 && (
            <div className="data-card">
              <div className="data-card-header">
                <h3 className="data-card-title">⚠️ Low Stock Alerts <span className="badge badge-red" style={{ marginLeft: 8 }}>{stocks.length}</span></h3>
                <Link to="/admin/products" className="btn btn-ghost btn-sm">Manage Products</Link>
              </div>
              <div className="table-wrap">
                <table>
                  <thead><tr><th>Product</th><th>Status</th><th>Action</th></tr></thead>
                  <tbody>
                    {stocks.map((n, i) => (
                      <tr key={i}>
                        <td>{n.productName}</td>
                        <td><span className="badge badge-red">{n.message}</span></td>
                        <td><Link to="/admin/products" className="btn btn-ghost btn-sm" style={{ fontSize: '0.75rem' }}>Restock →</Link></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </>
      )}
    </>
  )
}
