import { Routes, Route, Navigate } from 'react-router-dom'
import StoreLayout from './components/StoreLayout'
import AdminLayout from './components/AdminLayout'
import ProtectedRoute from './components/ProtectedRoute'
import PublicOnlyRoute from './components/PublicOnlyRoute'

import Home from './pages/Home'
import Menu from './pages/Menu'
import Order from './pages/Order'
import Track from './pages/Track'
import Login from './pages/Login'

import Dashboard from './pages/admin/Dashboard'
import AdminOrders from './pages/admin/Orders'
import AdminProducts from './pages/admin/Products'
import AdminStaff from './pages/admin/Staff'
import AdminNotifications from './pages/admin/Notifications'

export default function App() {
  return (
    <Routes>
      {/* Storefront */}
      <Route element={<PublicOnlyRoute><StoreLayout /></PublicOnlyRoute>}>
        <Route path="/" element={<Home />} />
        <Route path="/menu" element={<Menu />} />
        <Route path="/order" element={<Order />} />
        <Route path="/track" element={<Track />} />
      </Route>

      <Route path="/login" element={<Login />} />

      {/* Admin (Supabase Auth protected) */}
      <Route
        path="/admin"
        element={
          <ProtectedRoute>
            <AdminLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Dashboard />} />
        <Route path="orders" element={<AdminOrders />} />
        <Route path="notifications" element={<AdminNotifications />} />
        <Route
          path="products"
          element={<ProtectedRoute requireAdmin><AdminProducts /></ProtectedRoute>}
        />
        <Route
          path="staff"
          element={<ProtectedRoute requireAdmin><AdminStaff /></ProtectedRoute>}
        />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
