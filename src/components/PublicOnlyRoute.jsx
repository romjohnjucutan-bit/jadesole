import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Redirect staff/admin away from public storefront pages.
export default function PublicOnlyRoute({ children }) {
  const { loading, isLoggedIn, isStaff } = useAuth()

  if (loading) {
    return (
      <div className="flex-center" style={{ minHeight: '60vh', color: 'var(--text-dim)' }}>
        Loading…
      </div>
    )
  }

  if (isLoggedIn && isStaff) {
    return <Navigate to="/admin" replace />
  }

  return children
}
