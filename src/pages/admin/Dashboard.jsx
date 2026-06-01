import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../../supabaseClient'
import { useAuth } from '../../context/AuthContext'
import { peso, statusBadgeClass, formatDate } from '../../lib/config'

export default function Dashboard() {
  const { profile } = useAuth()
  const [stats, setStats] = useState({ products: 0, orders: 0, staff: 0, revenue: 0 })
  const [recent, setRecent] = useState([])

  useEffect(() => {
    async function load() {
      const [{ count: products }, { count: orders }, { count: staff }] = await Promise.all([
        supabase.from('products').select('*', { count: 'exact', head: true }),
        supabase.from('orders').select('*', { count: 'exact', head: true }),
        supabase.from('staff_profiles').select('*', { count: 'exact', head: true }),
      ])
      const { data: completed } = await supabase
        .from('orders').select('total_amount').eq('status', 'Completed')
      const revenue = (completed ?? []).reduce((s, o) => s + Number(o.total_amount), 0)
      const { data: recentOrders } = await supabase
        .from('orders').select('*').order('created_at', { ascending: false }).limit(10)

      setStats({ products: products ?? 0, orders: orders ?? 0, staff: staff ?? 0, revenue })
      setRecent(recentOrders ?? [])
    }
    load()
  }, [])

  return (
    <>
      <div className="dash-header">
        <h1 className="dash-title">Dashboard</h1>
        <span className="text-dim" style={{ fontSize: '0.85rem' }}>Welcome back, {profile?.name} 👋</span>
      </div>

      <div className="stats-grid">
        <div className="stat-card"><div className="stat-value">{stats.products}</div><div className="stat-label">Products</div></div>
        <div className="stat-card"><div className="stat-value">{stats.orders}</div><div className="stat-label">Total Orders</div></div>
        <div className="stat-card"><div className="stat-value">{stats.staff}</div><div className="stat-label">Staff Members</div></div>
        <div className="stat-card"><div className="stat-value">{peso(stats.revenue).replace('.00', '')}</div><div className="stat-label">Revenue</div></div>
      </div>

      <div className="data-card">
        <div className="data-card-header">
          <h3 className="data-card-title">Recent Orders</h3>
          <Link to="/admin/orders" className="btn btn-ghost btn-sm">View All</Link>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
              {recent.map((o) => (
                <tr key={o.id}>
                  <td><span className="text-gold" style={{ fontFamily: 'monospace' }}>{o.order_id}</span></td>
                  <td>{o.customer_name}</td>
                  <td>{peso(o.total_amount)}</td>
                  <td style={{ textTransform: 'uppercase', fontSize: '0.8rem' }}>{o.payment_method}</td>
                  <td><span className={`badge ${statusBadgeClass(o.status)}`}>{o.status}</span></td>
                  <td style={{ fontSize: '0.8rem', color: 'var(--text-dim)' }}>{formatDate(o.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  )
}
