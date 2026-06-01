import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

// Keep staff/admin out of the public storefront — but never block customers.
// The storefront renders immediately; we only redirect once we positively
// know the visitor is signed-in staff. (No "Loading…" gate = no auth lock.)
export default function PublicOnlyRoute({ children }) {
  const { loading, isLoggedIn, isStaff } = useAuth()

  if (!loading && isLoggedIn && isStaff) {
    return <Navigate to="/admin" replace />
  }

  return children
}
