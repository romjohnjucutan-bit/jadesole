import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Guards admin routes. requireAdmin=true blocks non-admin staff too.
export default function ProtectedRoute({ children, requireAdmin = false }) {
  const { loading, isLoggedIn, isAdmin, isStaff } = useAuth()
  const location = useLocation()

  if (loading) {
    return (
      <div className="flex-center" style={{ minHeight: '60vh', color: 'var(--text-dim)' }}>
        Loading…
      </div>
    )
  }

  if (!isLoggedIn || !isStaff) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />
  }

  if (requireAdmin && !isAdmin) {
    return <Navigate to="/admin" replace />
  }

  return children
}
