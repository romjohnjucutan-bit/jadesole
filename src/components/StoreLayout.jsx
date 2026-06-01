import { Outlet } from 'react-router-dom'
import Navbar from './Navbar'
import Footer from './Footer'

// Public storefront chrome: fixed navbar + page + footer.
export default function StoreLayout() {
  return (
    <>
      <Navbar />
      <div className="page-wrap" style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
        <Outlet />
        <Footer />
      </div>
    </>
  )
}
