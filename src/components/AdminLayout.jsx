import { useEffect, useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import Navbar from './Navbar'
import { useAuth } from '../context/AuthContext'
import { getNotifications } from '../lib/notifications'

export default function AdminLayout() {
  const { isAdmin, signOut } = useAuth()
  const navigate = useNavigate()
  const [counts, setCounts] = useState({ order: 0, stock: 0, total: 0 })

  useEffect(() => {
    let active = true
    getNotifications(isAdmin).then(({ items }) => {
      if (!active) return
      const order = items.filter((n) => n.type === 'order').length
      const stock = items.filter((n) => n.type === 'stock').length
      setCounts({ order, stock, total: items.length })
    })
    return () => { active = false }
  }, [isAdmin])

  async function handleLogout() {
    await signOut()
    navigate('/login')
  }

  return (
    <>
      <Navbar />
      <div className="page-wrap">
        <div className="dashboard-layout">
          <div className="sidebar">
            <div className="sidebar-section">
              <div className="sidebar-label">Admin Panel</div>
              <ul className="sidebar-nav">
                <li><NavLink to="/admin" end>📊 Overview</NavLink></li>
              </ul>
            </div>
            <div className="sidebar-section">
              <div className="sidebar-label">Management</div>
              <ul className="sidebar-nav">
                {isAdmin && (
                  <li>
                    <NavLink to="/admin/products">
                      👟 Products
                      {counts.stock > 0 && <span className="sidebar-badge sidebar-badge--warn">{counts.stock}</span>}
                    </NavLink>
                  </li>
                )}
                <li>
                  <NavLink to="/admin/orders">
                    📦 Orders
                    {counts.order > 0 && <span className="sidebar-badge">{counts.order}</span>}
                  </NavLink>
                </li>
                {isAdmin && <li><NavLink to="/admin/staff">👤 Staff</NavLink></li>}
              </ul>
            </div>
            <div className="sidebar-section">
              <div className="sidebar-label">Alerts</div>
              <ul className="sidebar-nav">
                <li>
                  <NavLink to="/admin/notifications">
                    🔔 Notifications
                    {counts.total > 0 && <span className="sidebar-badge">{counts.total > 9 ? '9+' : counts.total}</span>}
                  </NavLink>
                </li>
              </ul>
            </div>
            <div className="sidebar-section">
              <div className="sidebar-label">Store</div>
              <ul className="sidebar-nav">
                <li>
                  <a href="#logout" onClick={(e) => { e.preventDefault(); handleLogout() }}>🚪 Logout</a>
                </li>
              </ul>
            </div>
          </div>
          <div className="dashboard-main">
            <Outlet />
          </div>
        </div>
      </div>
    </>
  )
}
