import { Link, NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Navbar() {
  const { isLoggedIn, isStaff } = useAuth()
  const staff = isLoggedIn && isStaff

  return (
    <nav className="navbar">
      <Link to={staff ? '/admin' : '/'} className="nav-brand">
        <div className="nav-logo-icon">👟</div>
        <div className="nav-brand-text">Jade <span>Sole</span></div>
      </Link>
      <ul className="nav-links">
        {staff ? (
          // Signed-in staff/admin: no customer storefront links.
          <li><NavLink to="/admin">Dashboard</NavLink></li>
        ) : (
          <>
            <li><NavLink to="/" end>Home</NavLink></li>
            <li><NavLink to="/menu">Collection</NavLink></li>
            <li><NavLink to="/track">Track Order</NavLink></li>
            <li><NavLink to="/login">Admin / Staff Login</NavLink></li>
            <li><NavLink to="/order" className="nav-cart-btn">Order Now</NavLink></li>
          </>
        )}
      </ul>
    </nav>
  )
}
