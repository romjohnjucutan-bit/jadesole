import { useEffect, useRef, useState } from 'react'
import { Link, NavLink, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { getNotifications } from '../lib/notifications'
import { timeAgo } from '../lib/config'

export default function Navbar() {
  const { isLoggedIn, isAdmin } = useAuth()
  const [open, setOpen] = useState(false)
  const [notif, setNotif] = useState({ items: [], count: 0 })
  const wrapRef = useRef(null)
  const location = useLocation()

  useEffect(() => {
    let active = true
    if (isLoggedIn) {
      getNotifications(isAdmin).then((n) => { if (active) setNotif(n) })
    } else {
      setNotif({ items: [], count: 0 })
    }
    return () => { active = false }
  }, [isLoggedIn, isAdmin, location.pathname])

  useEffect(() => {
    function onClick(e) {
      if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false)
    }
    document.addEventListener('click', onClick)
    return () => document.removeEventListener('click', onClick)
  }, [])

  return (
    <nav className="navbar">
      <Link to="/" className="nav-brand">
        <div className="nav-logo-icon">👟</div>
        <div className="nav-brand-text">Jade <span>Sole</span></div>
      </Link>
      <ul className="nav-links">
        <li><NavLink to="/" end>Home</NavLink></li>
        <li><NavLink to="/menu">Collection</NavLink></li>
        <li><NavLink to="/track">Track Order</NavLink></li>

        {isLoggedIn ? (
          <>
            <li><Link to={isAdmin ? '/admin' : '/admin'}>Dashboard</Link></li>
            <li className="notif-wrapper" ref={wrapRef}>
              <button
                className="notif-bell"
                aria-label="Notifications"
                onClick={(e) => { e.stopPropagation(); setOpen((o) => !o) }}
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                  <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                {notif.count > 0 && (
                  <span className="notif-count">{notif.count > 9 ? '9+' : notif.count}</span>
                )}
              </button>
              <div className={`notif-dropdown${open ? ' open' : ''}`}>
                <div className="notif-header">
                  <span className="notif-header-title">Notifications</span>
                  {notif.count > 0 && <span className="notif-header-badge">{notif.count} new</span>}
                </div>
                <div className="notif-list">
                  {notif.items.length === 0 ? (
                    <div className="notif-empty">
                      <span style={{ fontSize: '1.8rem' }}>🔔</span>
                      <p>You're all caught up!</p>
                    </div>
                  ) : (
                    notif.items.map((n, i) => (
                      <Link key={i} to={n.link} className={`notif-item notif-item--${n.type}`} onClick={() => setOpen(false)}>
                        <div className="notif-item-icon">{n.icon}</div>
                        <div className="notif-item-body">
                          <div className="notif-item-title">{n.title}</div>
                          <div className="notif-item-msg">{n.message}</div>
                          {n.time && <div className="notif-item-time">{timeAgo(n.time)}</div>}
                        </div>
                      </Link>
                    ))
                  )}
                </div>
                {notif.count > 0 && (
                  <div className="notif-footer">
                    <Link to="/admin/orders" onClick={() => setOpen(false)}>View all orders →</Link>
                  </div>
                )}
              </div>
            </li>
          </>
        ) : (
          <li><NavLink to="/login">Admin / Staff Login</NavLink></li>
        )}

        <li><NavLink to="/order" className="nav-cart-btn">Order Now</NavLink></li>
      </ul>
    </nav>
  )
}
